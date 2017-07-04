$(document).ready(function() {
    $(".board-node").each(function() {
        $(this).click(function() {
            var turn = $("#matchconfig").attr("turn");
            if (turn != '1') {
                alert("no your turn.");
                return;
            }

            if ($(this).hasClass("white-piece") || $(this).hasClass("black-piece")) {
                alert("A piece is at this place.");
                return;
            }

            var place = $(this).attr("place");
            console.debug(place);

            var matchid = $("#matchconfig").attr("match");
            __ajax_and_reload("client.wuzi.place", {matchid: matchid, place: place});
        });
    });
});

