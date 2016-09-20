(function ($) {
    $(function () {
        $(document).ajaxStart(function () {
            window.scrollTo(0, 0);
            $("#indicator").show();
        });
        $(document).ajaxStop(function () {
            $("#indicator").hide();
        });

        var daysToEvents = {};
        var daysToDates = {};
        var availableSignups = [];
        var areaToSignups = {};
        $.get("assets/php/gab_volunteer_signup.php", function (signups) {
            signups = signups || [];
            jQuery.map(signups, function (signup, index) {
                if (signup && !signup.email && signup.area) {
                    availableSignups.push(signup);
                    if (areaToSignups[signup.area]) {
                        areaToSignups[signup.area].push(signup);
                    } else {
                        areaToSignups[signup.area] = [signup];
                    }
                }
            });
            var areas = _.uniq(_.keys(areaToSignups)).sort();
            var $select = $("#what select");
            var html = ["<option value = ''></option>"];
            $(areas).each(function (index) {
                html.push("<option value = \"");
                html.push(this);
                html.push("\">");
                html.push(this);
                html.push(" (");
                html.push(areaToSignups[this].length);
                html.push(areaToSignups[this].length !== 1 ? " slots " : " slot ");
                html.push("available)");
                html.push("</option>");
            });
            $select.html(html.join(''));
            $select.selectpicker('refresh');

        }, "json");

        $('select').selectpicker();

        $("#what select").change(function () {
            $("#when, #details").hide();

            var $this = $(this);
            var area = $this.val();
            if (!area) {
                $("#what select").empty().selectpicker('refresh');
                $("#details:input").val("");
                return;
            }


            var timesToAvailableSignupsForArea = [];
            jQuery.map(areaToSignups[area], function (signup, index) {
                var time = [signup.date + ", " + signup.time].join("");
                if (signup && time) {
                    if (timesToAvailableSignupsForArea[time]) {
                        timesToAvailableSignupsForArea[time].push(signup);
                    } else {
                        timesToAvailableSignupsForArea[time] = [signup];
                    }
                }
            });

            var html = _.values(_.mapObject(timesToAvailableSignupsForArea, function (signups, time) {
                return ["<option value=\"", signups[0].row, "\">", time, "</option>"].join("");
            })).join("");
            $("#when select").html("<option value = \"\"></option>" + html);
            $("#when select").selectpicker('refresh');
            $("#when").show();
        });

        $("#when select").change(function () {
            var $this = $(this);
            var val = $this.val();
            if (!val) {
                $("#details").hide();
                $("#details:input").val("");
                return;
            }
            $("#details").show();
        });

        $("#gabVolunteerSignupForm").validator().on("submit", function (e) {
            if (e.isDefaultPrevented()) {
                return;
            }

            e.preventDefault();
            var $this = $(this);
            $.post("assets/php/gab_volunteer_signup.php", $this.serialize(), function (response) {
                if (response !== true) {
                    alert("Uh-oh we had some trouble requesting your signup. Please contact the web master.");
                } else {
                    alert("We have requested for your signup. You will receive a confirmation  e-mail soon! If not see please see the GAB team");
                }
                window.location.reload();
            }, "json");

        });
    });
})(jQuery);