

function join(roomid, playerid) {
    __ajax("client.wuzi.join", {room: roomid, player: playerid}, true);
}

function leave(roomid, playerid) {
    __ajax("client.wuzi.leave", {room: roomid, player: playerid}, true);
}


$(document).ready(function() {

});

