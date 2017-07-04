$(document).ready(function() {
    $(".match").each(function() {
        $(this).click(function() {
            var match = $(this).attr("match");
            document.location.href = "?client/wuzi/match&match=" + match;
        });
    });
});

