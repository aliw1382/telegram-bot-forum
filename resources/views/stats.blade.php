<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>آمار</title>

    <style type="text/css">

        @font-face {
            src: url("{{ asset('assets/font/farhang/Farhang2-ExtraBold.ttf')  }}");
            font-family: "Fargang";
        }

        @font-face {
            src: url("{{ asset('assets/font/vazir/Vazir.ttf')  }}");
            font-family: "Vazir";
        }

        #divMyChart {
            max-height: 600px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

    </style>

</head>
<body>

<div id="divMyChart">
    <canvas id="myChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    const ctx = document.getElementById( 'myChart' );
    const labels = [];
    const avg = [];
    const count = [];
    const participate = [];

    @foreach( $stats as $item )

    @foreach( $item as $key => $stat )

    labels.push( "{{ $key  }}" );
    count.push( {{ $stat['count']  }} );
    avg.push( {{ $stat['avg']  }} );
    participate.push( {{ $stat['participate']  }} );

    @endforeach

        @endforeach

        Chart.defaults.font.family = "Vazir";

    new Chart( ctx , {

        type : 'bar' ,

        data : {
            labels : labels ,
            datasets : [
                {
                    label : 'تعداد شرکت کنندگان' ,
                    data : participate ,
                    borderWidth : 1
                } ,
                {
                    label : 'تعداد رای ها' ,
                    data : count ,
                    borderWidth : 2
                } ,
                {
                    label : 'میانگین رضایت' ,
                    data : avg ,
                    borderWidth : 3
                }
            ]
        } ,
        animation : {
            duration : 2000
        } ,
        options : {
            scales : {
                y : {
                    beginAtZero : true
                }
            } ,
            plugins : {
                legend : {
                    labels : {
                        font : {
                            size : 20 ,
                            family : "Fargang"
                        }
                    } ,
                }
            }
        } ,
    } );

</script>

</body>
</html>
