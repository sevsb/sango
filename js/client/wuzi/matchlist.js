$(document).ready(function() {
    $(".match").click(function() {
        console.debug(this);
        var match = $(this).attr("match");
        // console.debug(match);
        document.location.href = "?client/wuzi/match&match=" + match;
    });
});

