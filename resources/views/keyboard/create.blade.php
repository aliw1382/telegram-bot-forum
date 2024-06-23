<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <title>Keyboard Building</title>

</head>

<body class="p-5">

<div class="container">
    <div class="row">
        <div class="col-2"></div>
        <div class="col-8">

            <form>
                <div class="form-group">
                    <label for="text">Text:</label>
                    <input type="text"
                           class="form-control"
                           id="text"
                           aria-describedby="emailHelp"
                    >
                </div>

                <div class="form-group">
                    <label for="url">Url:</label>
                    <input type="text"
                           class="form-control"
                           id="url"
                    >
                </div>

                <div class="form-group">
                    <label for="data">CallbackData:</label>
                    <input type="text"
                           class="form-control"
                           id="data"
                    >
                </div>

                <div class="form-group">
                    <label for="query">SwitchInlineQuery:</label>
                    <input type="text"
                           class="form-control"
                           id="query"
                    >
                </div>

                <br>

                <kbd class="mt-3" id="value">['text' => '']</kbd>

                <br>
                <br>

                <kbd class="mt-3" id="value-telegram">$telegram->buildInlineKeyboardButton('', '', '','')</kbd>

            </form>


        </div>
    </div>
</div>

<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script type="text/javascript">
    $( document ).ready( function ()
    {
        $( "form" ).on( 'input' , function ()
        {
            var elementText = $( "#text" );
            var elementData = $( "#data" );
            var elementQuery = $( "#query" );
            var elementUrl = $( "#url" );

            var text = '';

            text += "['text' => '" + elementText.val() + "'";
            if ( elementData.val() !== "" )
            {
                text += " , 'callback_data' => '" + elementData.val() + "'";
            }
            if ( elementUrl.val() !== "" )
            {
                text += " , 'url' => '" + elementUrl.val() + "'";
            }
            if ( elementQuery.val() !== "" )
            {
                text += " , 'switch_inline_query' => '" + elementQuery.val() + "'";
            }
            $( '#value' ).text( text + ']' );
            $( '#value-telegram' ).text( "$telegram->buildInlineKeyboardButton('" + elementText.val() + "', '" + elementUrl.val() + "', '" + elementData.val() + "','" + elementQuery.val() + "')" );
        } );
    } );
</script>
</body>
</html>
