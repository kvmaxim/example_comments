var setElemCenterDisplay = function(elem) {

    var height_wind = $(window).height();
    var scroll = $("html").scrollTop();

    var elem_top = $(elem).offset().top;
    var new_position = elem_top - (height_wind/2);

    if (new_position <0) new_position = 0;

    $('html, body').animate({scrollTop: new_position}, 300);
}

var hideQuote = function(elem) {

    $(elem).closest('.comments__quote').addClass('hide');
}

var answerComment = function (elem, user_name) {

    $(elem).closest('.comments').find('.comments__publisher-wrap').find('.comments__quote').removeClass('bg-anim')

    var name_surname_master_comment = $(elem).closest('.comments__body').find('#id_ns_master_comment').text();
    var message = $(elem).closest('.comments__body').find('#id_message').text();
    var index_comment = $(elem).closest('.comments__item').attr('indx_comment');

    $(elem).closest('.comments').find('#id_quote_ns_user_for_answer_block').text(name_surname_master_comment);
    $(elem).closest('.comments').find('#id_quote_ns_user_for_answer_block').attr('indx_comment_quote', index_comment);
    $(elem).closest('.comments').find('#id_quote_message_for_answer_block').text(message);

    $(elem).closest('.comments').find('.comments__quote').removeClass('hide');

    var textarea = $(elem).closest('.comments').find('#post-textarea').focus();
    showPublisherBoxComments(textarea[0]);
    showPublisherBox(textarea[0]);
    focusPublisherBox(textarea[0])
    setElemCenterDisplay(textarea);

    $(elem).closest('.comments').find('.comments__publisher-wrap').find('.comments__quote').addClass('bg-anim');
}

var deleteThisComment = function (elem, indx_comment) {

    if (fl_wait_delete_comment === true) return;
    var fl_wait_delete_comment = true;

    var comments = $(elem).closest('.comments');
    var id_comments = $(comments).attr('id_comments');

    var data = {
        _csrf: $('meta[name="csrf-token"]').attr('content'),
        id_comments: id_comments,
        indx_comment: indx_comment,
    }

    $.ajax({
        url: '/comments/delete-comment/',
        type: 'POST',
        dataType: 'json',
        data: {data: JSON.stringify(data)},
        success: function (res) {

            var block_comments = $('[id_comments=' + res.id_comments + ']');

            $(block_comments).find('[indx_comment= ' + res.indx_comment + ']').remove();

            var elem = $('[id_post=' + res.id_comments + ']').find('.number_comments');

            var number_comments = parseInt(elem.text(), 10);
            number_comments--;
            if (number_comments >= 0) elem.text(number_comments);

            fl_wait_delete_comment = false;
        },
        error: function (mes) {
            f_error('Error: 75');
            fl_wait_delete_comment = false;
        }
    });
}

var createNewComment = function (elem) {

    if (fl_wait_create_new_comment === true) return;
    var fl_wait_create_new_comment = true;

    var comments = $(elem).closest('.comments');
    var id_comments = $(comments).attr('id_comments');
    var place_creation = $(comments).attr('place_creation');

    var message = $(elem).closest('.comments__publisher-wrap').find('#id_textarea').val();
    if (message.length == 0 )
    {
        fl_wait_create_new_comment = false;
        return;
    }

    var fl_hide_quote = $(elem).closest('.comments__publisher-wrap').find('.comments__quote').hasClass('hide');

    var indx_comment_quote = -1;

    if (fl_hide_quote == false) {
        indx_comment_quote =  $(elem).closest('.comments').find('#id_quote_ns_user_for_answer_block').attr('indx_comment_quote');
    }

    var indx_up_comment = $(elem).closest('.comments').attr('indx_up_comment');
    var indx_down_comment = $(elem).closest('.comments').attr('indx_down_comment');

    var data = {
        _csrf: $('meta[name="csrf-token"]').attr('content'),
        id_comments: id_comments,
        indx_comment_quote: indx_comment_quote,
        indx_up_comment: indx_up_comment,
        indx_down_comment: indx_down_comment,
        message: message,
        'place_creation': place_creation,
    }

    if (place_creation == 'course_ads') {
        data['id_course'] = $("[id_course]").attr('id_course');
        data['indx_ad'] = $(elem).closest('.comments__item').attr('indx_ad');
    }

    if (place_creation == 'course_quesitons_and_answers') {
        data['id_course'] = $("[id_course]").attr('id_course');
        data['id_lesson'] = $(elem).closest('.panel-courses__body').attr('id_lesson');
        data['indx_question'] = $(elem).closest('.panel-courses__body').attr('indx_question');
    }

    $.ajax({
        url: '/comments/create-comment/',
        type: 'POST',
        dataType: 'json',
        data: {data: JSON.stringify(data)},
        success: function (res) {
            $('[id_list_comments=' + res.id_comments + ']').append(res.list_new_comments);

            initAll();

            $.each($('a.post-time'), function (index, value) {
                var new_time = f_convert_time(value.text);
                if (new_time != false) value.text = new_time;
            });

            var elem_footer = $('[id_post=' + res.id_comments + ']').find('.number_comments');
            elem_footer.text(res.number_comments);

            var elem = $('[id_comments=' + res.id_comments + ']');


            $(elem).attr('indx_down_comment', res.indx_down_comment);

            $(elem).find('#id_textarea').val("");

            $(elem).find('.emoji-wysiwyg-editor').text("");

            $(elem_footer).focus();

            setElemCenterDisplay($(elem).find('.comments__publisher-wrap'));
            $(elem).find('.comments__publisher-wrap').find('.comments__quote').addClass('hide');

            fl_wait_create_new_comment = false;

        },
        error: function (mes) {
            f_error('Error: 76');
            fl_wait_create_new_comment = false;
        }
    });

}

var getComments = function (elem) {

    if (fl_wait_get_comments === true) return;
    fl_wait_get_comments = true;

    var indx_up_comment = $(elem).closest('.comments').attr('indx_up_comment');
    var indx_down_comment = $(elem).closest('.comments').attr('indx_down_comment');
    var id_comments = $(elem).closest('.comments').attr('id_comments');

    var data = {
        _csrf: $('meta[name="csrf-token"]').attr('content'),
        id_comments: id_comments,
        indx_up_comment: indx_up_comment,
        indx_down_comment: indx_down_comment,
    }

    $.ajax({
        url: '/comments/get-comments/',
        type: 'POST',
        xhr: progressBar,
        dataType: 'json',
        data: {data: JSON.stringify(data)},
        success: function (res) {

            $('[id_comments=' + res.id_comments + ']').attr('indx_up_comment', res.indx_up_comment);
            $('[id_comments=' + res.id_comments + ']').attr('indx_down_comment', res.indx_down_comment);

            $('[id_list_comments=' + res.id_comments + ']').prepend(res.list_new_comments_up);
            if (res.list_new_comments_down != '') $('[id_list_comments=' + res.id_comments + ']').append(res.list_new_comments_down);

            var elem_footer = $('[id_post=' + res.id_comments + ']').find('.number_comments');
            elem_footer.text(res.number_comments);

            var remaining_comments = parseInt($('[id_text_load_some_comment=' + res.id_comments + ']').text(), 10);
            remaining_comments = remaining_comments - res.number_comments_in_up_block;

            if (remaining_comments <= 0) $('[id_text_load_some_comment=' + res.id_comments + ']').closest('.comments__more').remove();
            else $('[id_text_load_some_comment=' + res.id_comments + ']').text(remaining_comments);

            $.each($('a.post-time'), function (index, value) {
                var new_time = f_convert_time(value.text);
                if (new_time != false) value.text = new_time;
            });

            initAll();
            fl_wait_get_comments = false;

        },
        error: function (mes) {
            f_error('Error: 77');
            fl_wait_get_comments = false;
        }
    });
}

var goToComment = function (elem, indx_quote) {

    if (fl_wait_go_to_comment === true) return;
    fl_wait_go_to_comment = true;

    var indx_up_comment = $(elem).closest('.comments').attr('indx_up_comment');
    var indx_down_comment = $(elem).closest('.comments').attr('indx_down_comment');
    var id_comments = $(elem).closest('.comments').attr('id_comments');

    var data = {
        _csrf: $('meta[name="csrf-token"]').attr('content'),
        id_comments: id_comments,
        indx_up_comment: indx_up_comment,
        indx_down_comment: indx_down_comment,
        indx_quote: indx_quote,
    }

    var comment_quote = $(elem).closest('.comments').find('[indx_comment= ' + indx_quote + ']');

    if (comment_quote.length !=0)
    {
        $(comment_quote).removeClass('bg-anim');
        setElemCenterDisplay(comment_quote);
        fl_wait_go_to_comment = false;
        $(comment_quote).addClass('bg-anim');
        return;
    }

    $.ajax({
        url: '/comments/go-to-comment/',
        type: 'POST',
        xhr: progressBar,
        dataType: 'json',
        data: {data: JSON.stringify(data)},
        success: function (res) {

            $('[id_comments=' + res.id_comments + ']').attr('indx_up_comment', res.indx_up_comment);
            $('[id_comments=' + res.id_comments + ']').attr('indx_down_comment', res.indx_down_comment);

            $('[id_list_comments=' + res.id_comments + ']').prepend(res.list_new_comments_up);
            if (res.list_new_comments_down != '') $('[id_list_comments=' + res.id_comments + ']').append(res.list_new_comments_down);

            var elem_footer = $('[id_post=' + res.id_comments + ']').find('.number_comments');
            elem_footer.text(res.number_comments);

            var remaining_comments = parseInt($('[id_text_load_some_comment=' + res.id_comments + ']').text(), 10);
            remaining_comments = remaining_comments - res.number_comments_in_up_block;

            if (remaining_comments <= 0) $('[id_text_load_some_comment=' + res.id_comments + ']').closest('.comments__more').remove();
            else $('[id_text_load_some_comment=' + res.id_comments + ']').text(remaining_comments);

            $.each($('a.post-time'), function (index, value) {
                var new_time = f_convert_time(value.text);
                if (new_time != false) value.text = new_time;
            });

            initAll();

            if (!res.fl_has_comment_for_quote) notify('danger', res.message_if_comment_not_exist);

            fl_wait_get_comments = false;

        },
        error: function (mes) {
            f_error('Error: 78');
            fl_wait_get_comments = false;
        }
    });
}

var getBlockComments = function (elem) {

    if (fl_wait_get_block_comments === true) return;
    fl_wait_get_block_comments = true;

    var csrf_token = $('meta[name="csrf-token"]').attr('content');
    var tmp_elem = $(elem).closest('.user-post');
    var id_comments = tmp_elem.attr('id_post');
    var place_creation = tmp_elem.attr('place_creation');

    if (id_comments === null || id_comments == undefined) {
        id_comments = $(elem).closest('.comments__item').find('.lesson-qa__inner-comments').attr('id_comment_container');
        place_creation =  $(elem).closest('.comments__item').find('.lesson-qa__inner-comments').attr('place_creation');
    }

    var comment_container = $("[id_comment_container=" + id_comments + "]");
    if (comment_container[0].children.length > 0) {
        comment_container[0].innerHTML = "";
        fl_wait_get_block_comments = false;
        return;
    }

    var data = {
        _csrf: $('meta[name="csrf-token"]').attr('content'),
        id_comments: id_comments,
        'place_creation' : place_creation,
    }

    $.ajax({
        url: '/comments/get-block-comments/',
        type: 'POST',
        dataType: 'json',
        data: {data: JSON.stringify(data)},
        success: function (res) {
            var comment_container = $("[id_comment_container=" + id_comments + "]");
            comment_container.append(res.block_comments);

            $.each($('a.post-time'), function (index, value) {
                var new_time = f_convert_time(value.text);
                if (new_time != false) value.text = new_time;
            });

            $('[id_comments=' + res.id_comments + ']').find('.number_comments').text(res.number_comments);
            initAll();

            fl_wait_get_block_comments = false;
        },
        error: function (mes) {
            f_error('Error: 79');
            fl_wait_get_block_comments = false;
        }
    });
}
