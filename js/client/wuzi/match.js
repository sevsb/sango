var ws = null;

var walkto = function(dir) {
    console.debug("walk to " + dir);
    var obj = {};
    obj.op = "walk";
    obj.to = dir;
    send(obj);
}

var send = function(obj) {
    var text = JSON.stringify(obj);
    console.debug("send: " + text);
    ws.send(text);
}

var send_op = function(op) {
    var text = '{"op": "' + op + '"}';
    console.debug("send: " + text);
    ws.send(text);
}

var login = function() {
    var me = $("#matchconfig").attr("me");
    var matchid = $("#matchconfig").attr("match");

    var obj = {};
    obj.op = "login";
    obj.player = me;
    send(obj);

    obj.op = "match";
    obj.match = matchid;
    send(obj);
}

var place_piece = function(place) {
    var obj = {};
    obj.op = "place";
    obj.place = place;
    send(obj);
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
        // __ajax_and_reload("client.wuzi.place", {matchid: matchid, place: place});

        place_piece(place);
    });

    var gridwidth = 0;
    $(".board-node").each(function() {
        if (gridwidth == 0) {
            gridwidth = $(this)[0].offsetWidth;
            if (gridwidth > 50) {
                gridwidth = 50;
            }
            console.debug(gridwidth);
        }
        $(this).css("width", gridwidth + "px");
        $(this).css("height", gridwidth + "px");

        w1 = gridwidth * 18 / 25;
        l = gridwidth * 0 / 25;
        t = gridwidth * 2 / 25;
        $(this).children(".piece").css("width", w1 + "px");
        $(this).children(".piece").css("height", w1 + "px");
        $(this).children(".piece").css("left", l + "px");
        $(this).children(".piece").css("top", t + "px");
    });


    ws = new WebSocket('ws://114.215.82.75:19504');
    ws.onopen = function(evt) {
        // msg.innerHTML = ws.readyState;
        console.debug(ws);
        login();
    };

    ws.onclose = function(evt) {
        console.debug(evt);
    };

    ws.onmessage = function(evt) {
        var obj = eval('(' + evt.data + ')');
        console.debug(obj);
        if (obj.op == "tip") {
            var ret = obj.ret;
            if (typeof(obj.reason) == "string") {
                alert(obj.reason);
            }
        } else if (obj.op == "place") {
            var place = obj.place;
            var player = obj.player;
            var pid1 = $(".player1").attr("pid");
            var pid2 = $(".player2").attr("pid");
            var clz = "white-piece";
            if (player == pid1) {
                clz = "black-piece";
            }
            $("div[place='" +place + "']").addClass(clz);
            // document.location.reload();
        }
    };

    ws.onerror = function(evt, e) {
        console.error(evt)
        // console.error(ws)
    };
});

