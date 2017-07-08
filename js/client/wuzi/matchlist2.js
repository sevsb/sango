
$(document).ready(function() {
    var matchlist = new Vue({
        el: "#matches",
        data: {
            visible: true,
            matches: null
        },
        methods: {
            join: function(event) {
                var target = event.target;
                var matchid = $(target).attr("matchid");
                document.location.href = "?client/wuzi/match&match=" + matchid;
            }
        }
    });

    __request("wechat.wuzi.matchlist", {}, function(res) {
        console.debug(res);
        matchlist.matches = res.data;
    });

});

