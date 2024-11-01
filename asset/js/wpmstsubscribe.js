jQuery(function($){

    $(".wpmst-subscribe-form").submit(function(e) {
        console.log($(this));
        var formIdentifier = $(this).data('wpmstformid');
        console.log(formIdentifier);
        $('#subscr'+formIdentifier+' .subscribe-result').hide();
        $('#subscr'+formIdentifier+' .ajax_call_in_progress').show();

        var data = $('#subscr'+formIdentifier).serialize();

        $.post(wpmst_ajax_object.ajaxurl, data // serializes the form's elements.
        , function(data) {
                console.log(data);

                $('#subscr'+formIdentifier+' .ajax_call_in_progress').hide();
                var resultObj = JSON.parse(data);
                console.log(data);
                console.log(resultObj);
                $('#subscr'+formIdentifier+' .subscribe-result-success').html('');
                $('#subscr'+formIdentifier+' .subscribe-result-error').html('');
                $('#subscr'+formIdentifier+' .subscribe-result-errorMsgs').html('');
                if(resultObj.res == true){
                    $('#subscr'+formIdentifier+' .subscribe-result-success').html(resultObj.resultMsg);
                    var successMessageElement = $('#subscr'+formIdentifier+' .subscribe-result-success').detach();
                    $('#subscr'+formIdentifier).empty().append(successMessageElement);
                }else{
                    $('#subscr'+formIdentifier+' .subscribe-result-error').html(resultObj.resultMsg);
                    jQuery.each(resultObj.errorMsgs, function(index, item) {
                        $('#subscr'+formIdentifier+' .subscribe-result-errorMsgs').append('<span>'+item['msg']+'</span><br/>');
                    });
                }
                $('.subscribe-result').show();


        });
        e.preventDefault(); // avoid to execute the actual submit of the form.
    });

    $(".wpmst-unsubscribe-form").submit(function(e) {
        console.log($(this));
        var formIdentifier = $(this).data('wpmstformid');
        console.log(formIdentifier);
        $('#unsubscr'+formIdentifier+' .unsubscribe-result').hide();
        $('#unsubscr'+formIdentifier+' .ajax_call_in_progress').show();

        var data = $('#unsubscr'+formIdentifier).serialize();
        console.log(data);
        $.post(wpmst_ajax_object.ajaxurl, data // serializes the form's elements.
            , function(data) {
                console.log(data);

                $('#unsubscr'+formIdentifier+' .ajax_call_in_progress').hide();
                var resultObj = JSON.parse(data);
                console.log(data);
                console.log(resultObj);
                $('#unsubscr'+formIdentifier+' .unsubscribe-result-success').html('');
                $('#unsubscr'+formIdentifier+' .unsubscribe-result-error').html('');
                $('#unsubscr'+formIdentifier+' .unsubscribe-result-errorMsgs').html('');
                if(resultObj.res == true){
                    $('#unsubscr'+formIdentifier+' .unsubscribe-result-success').html(resultObj.resultMsg);
                    var successMessageElement = $('#unsubscr'+formIdentifier+' .unsubscribe-result-success').detach();
                    $('#unsubscr'+formIdentifier).empty().append(successMessageElement);
                }else{
                    $('#unsubscr'+formIdentifier+' .unsubscribe-result-error').html(resultObj.resultMsg);
                    jQuery.each(resultObj.errorMsgs, function(index, item) {
                        $('#unsubscr'+formIdentifier+' .unsubscribe-result-errorMsgs').append('<span>'+item['msg']+'</span><br/>');
                    });
                }
                $('.unsubscribe-result').show();


            });
        e.preventDefault(); // avoid to execute the actual submit of the form.
    });

});