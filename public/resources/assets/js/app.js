$(function() {

    var base64decode = function(base64url) {

        //1) gmail api encodes in base64url
        //2) unicode characters aren't handled by atob see: https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
        //specifically the "Unicode problem" solution 2

        //base64url to base64
        base64Str = base64url.replace(/_/g, '/').replace(/-/g, '+');

        //byte array
        var byteArr = base64js.toByteArray(base64Str);

        //decode to utf-8
        return  new TextDecoderLite('utf-8').decode(byteArr);

    };


    //decode gmail api message
    $('.message-subject').on('click', function () {

        //clear the message
        $('#messageModalSubject').html('');
        $('#messageModalFrom').html('');
        $('#messageModalDate').html('');
        $('#messageModalText').html('');

        $.ajax({

            url: '/message/' + $(this).data('message-id'),
            method: "GET",
            dataType: "json"

        }).done(function (result) {

            //console.log(result);

            var msgText = base64decode(result.payload.parts[0].body.data);
            var msgSubject = '';
            var msgFrom = '';
            var msgDate = '';

            var headers = result.payload.headers;
            for (i = 0; i < headers.length; i++) {

                switch (headers[i].name) {

                    case 'Subject':
                        msgSubject = headers[i].value;
                        break;

                    case 'Date':
                        msgDate = headers[i].value;
                        break;

                    case 'From':
                        msgFrom = headers[i].value;
                        break;
                }
            }

            $('#messageModalSubject').text(msgSubject);
            $('#messageModalFrom').text(msgFrom);
            $('#messageModalDate').text(msgDate);
            $('#messageModalText').text(msgText);

        }).fail(function () {

            $('#messageModalSubject').html('Error');
            $('#messageModalText').html("Can't load message: " + $(this).data('message-id'));
        });
    });
});
