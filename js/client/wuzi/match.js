function refresh() {
    var matchid = $("#matchconfig").attr("match");
    var lastplace = $("#matchconfig").attr("lastplaceid");
    if (lastplace == -1) {
        return;
    }
    __ajax("client.wuzi.refresh", {matchid: matchid, placeid: lastplace}, true, function(data) {
        console.debug("no new place.");
    });
}

$(document).ready(function() {
    console.debug("docuemnt.ready.");
    $(".board-node").click(function() {
        // var turn = $("#matchconfig").attr("turn");
        // if (turn != '1') {
        //     alert("not your turn.");
        //     return;
        // }

        if ($(this).hasClass("white-piece") || $(this).hasClass("black-piece")) {
            alert("A piece is at this place.");
            return;
        }

        var place = $(this).attr("place");
        console.debug(place);

        var matchid = $("#matchconfig").attr("match");
        __ajax_and_reload("client.wuzi.place", {matchid: matchid, place: place});
    });

    var gridwidth = 0;
    $(".board-node").each(function() {
        if (gridwidth == 0) {
            gridwidth = $(this)[0].offsetWidth;
            console.debug(gridwidth);
        }
        $(this).css("height", gridwidth + "px");

        w1 = gridwidth * 18 / 25;
        l = gridwidth * 0 / 25;
        t = gridwidth * 2 / 25;
        $(this).children(".piece").css("width", w1 + "px");
        $(this).children(".piece").css("height", w1 + "px");
        $(this).children(".piece").css("left", l + "px");
        $(this).children(".piece").css("top", t + "px");
    });

    setInterval("refresh()", 2000);
});


