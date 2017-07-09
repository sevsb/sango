
$(document).ready(function() {
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
            place: {
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


});

