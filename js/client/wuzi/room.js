
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
                var roomid = this.rooms[room].info.id;
                room_op("watch", roomid);
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


    ws = new WebSocket('ws://www.wuziyi.cc:19503');
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
            alert(obj.message);
        } else if (obj.op == "refresh") {
            var rid = obj.info.id;
            /*
            var data = rooms.rooms;
            console.debug(data);
            console.debug(obj);
            return;
            for (var k in data) {
                if (data[k].info.id == rid) {
                    data[k] = obj;
                }
            }
            console.debug(data);
            return;
            rooms.rooms = data;
            */
        }
    };

    ws.onerror = function(evt, e) {
        console.error(evt)
    };

});

