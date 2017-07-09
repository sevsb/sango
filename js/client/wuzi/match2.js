
$(document).ready(function() {
    var ws = null;
    var send = function(obj) {
        var text = JSON.stringify(obj);
        console.debug("send: " + text);
        ws.send(text);
    };

    var place_piece = function(pls) {
        var obj = {};
        obj.op = "place";
        obj.place = pls;
        send(obj);
    };

    var login = function() {
        var obj = {};
        obj.op = "login";
        obj.token = login_token;
        obj.match = g_matchid;
        console.debug(obj);
        send(obj);
    }

    var match = new Vue({
        el: "#match",
        data: {
            visible: true,
            board: null,
            gridWidth: {
                width: '20px',
                height: '20px',
            },
            pieceWidth: {
                width: '18px',
                height: '18px',
                'line-height': '18px',
            },
            showIndex: false,
        },
        methods: {
            place: function(pls) {
                // console.debug(pls);
                place_piece(pls);
            }
        }
    });


    var resize = function() {
        var boardWidth = $(".main-wrapper")[0].offsetWidth;
        console.debug(boardWidth);
        var gridwidth = boardWidth / 15;
        console.debug(gridwidth);
        var obj = {
            width:  gridwidth  + "px",
            height : gridwidth  + "px"
        }
        match.gridWidth = obj;

        var piecewidth = gridwidth - 2;
        var obj2 = {
            width: piecewidth + "px",
            height: piecewidth + "px",
            'line-height': piecewidth + "px",
        }
        match.pieceWidth = obj2;
    };
    resize();


    __request("wechat.wuzi.match", {match: g_matchid}, function(res) {
        console.debug(res);
        match.board = res.data;
    });

    ws = new WebSocket(wuzi_server);
    ws.onopen = function(evt) {
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
            alert(obj.data.message);
        } else if (obj.op == "board") {
            var data = obj.data;
            match.board = obj.data;
        }
    };

    ws.onerror = function(evt, e) {
        console.error(evt)
    };


});

