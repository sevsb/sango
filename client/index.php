<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no"/>
        <script src="jquery-2.1.1.min.js"></script>
        <title></title>
        <style>
            .grid {
                border: solid 1px; #ccc;
                width: 20px;
                height: 20px;
                float: left;
            }
            .red {
                background-color: #6cf;
            }
            .green {
                background-color: green;
            }
        </style>
        <script>
            $(document).ready(function() {
                $(".grid").each(function() {
                    $(this).click(function() {
                        if ($(this).hasClass("red")) {
                            $(this).removeClass("red");
                            $(this).addClass("green");
                        } else {
                            $(this).removeClass("green");
                            $(this).addClass("red");
                        }
                    });
                });
            });
        </script>
    </head>
    <body>
        <?php for ($i = 0; $i <= 1000; $i++) { ?>
        <div class="grid red" index="<?php echo $i; ?>"></div>
        <?php } ?>
    </body>
</html>

