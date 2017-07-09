
$(document).ready(function() {
    var ws = null;
    var send = function(obj) {
        var text = JSON.stringify(obj);
        console.debug("send: " + text);
        ws.send(text);
    };

    var room_op = function(op, roomid) {
        var obj = {};
        obj.op = op;
        obj.roomid = roomid;
        send(obj);
    };

    var login = function() {
        var obj = {};
        obj.op = "login";
        obj.token = login_token;
        console.debug(obj);
        send(obj);
    }

    var rooms = new Vue({
        el: "#rooms",
        data: {
            visible: true,
            rooms: null
        },
        methods: {
            showNick: function(event) {
                var target = event.target;
                var room = $(target).attr("room");
                var player = $(target).attr("player");

                for (var k in this.rooms) {
                    for (var pid in this.rooms[k].players) {
                        this.rooms[k].players[pid].showNick = false;
                    }
                }

                this.rooms[room].players[player].showNick = true;
            },
            sit: function(event) {
                var room = $(event.target).attr("room");
                var roomid = this.rooms[room].info.id;
                room_op("sit", roomid);
            },
            stand: function(event) {
                var room = $(event.target).attr("room");
                var roomid = this.rooms[room].info.id;
                room_op("stand", roomid);
            },
            start: function(event) {
                var room = $(event.target).attr("room");
                var roomid = this.rooms[room].info.id;
                room_op("start", roomid);
            },
            watch: function(event) {
                var room = $(event.target).attr("room");
                var matchid = this.rooms[room].info.match;
                document.location.href = "?client/wuzi/match&match=" + matchid;
            }
        }
    });

    __request("wechat.wuzi.rooms", {}, function(data) {
        for (var k in data) {
            for (var pid in data[k].players) {
                data[k].players[pid].showNick = false;
            }
        }
        rooms.rooms = data;
    });


    ws = new WebSocket(room_server);
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
        } else if (obj.op == "refresh") {
            var data = obj.data;
            for (var k in data) {
                for (var pid in data[k].players) {
                    data[k].players[pid].showNick = false;
                }
            }
            rooms.rooms = data;
        } else if (obj.op == "match") {
            var mid = obj.data.match;
            document.location.href = "?client/wuzi/match&match=" + mid;
        }
    };

    ws.onerror = function(evt, e) {
        console.error(evt)
    };

});

