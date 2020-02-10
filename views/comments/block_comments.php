<div class="comments" indx_up_comment="<?=$data['indx_up_comment']?>" indx_down_comment="<?=$data['indx_down_comment']?>" id_comments = "<?=$data['id_comments']?>" place_creation = "<?=$data['place_creation']?>">

    <?php if($data['remaining_comments'] >0): ?>
        <a class="comments__more" href="javascript:void(0)" onclick="getComments(this);">
            <?= \Yii::t('common' , 'LABEL_SOME_COMMENTS'); ?> (<span id_text_load_some_comment="<?=$data['id_comments']?>" ><?=$data['remaining_comments']?></span>)<i class="fal fa-angle-down show-more"></i>
        </a>
    <?php endif; ?>

    <ul class="comments__list" id_list_comments="<?=$data['id_comments']?>">

        <?php echo $data['list_comments'];?>

    </ul>

    <?php if ($data['can_create_comment']):?>

        <div class="comments__publisher-wrap">
            <img class="comments__avatar" src="<?=$data['user_path_avatar']?>">

            <form class="publisher-box post comments__publisher" id="publisher-box-form">

                <a class="comments__quote hide" href="javascript:void(0)">
                    <div id = "id_quote_ns_user_for_answer_block" indx_comment_quote = "index_quote" class="comments__user" href="javascript:void(0)">USER FIO HIDE</div>
                    <div class="comments__text-wrap">
                        <p id = "id_quote_message_for_answer_block" class="comments__text">QUOTE HIDE</p>
                    </div>
                    <div class="fal fa-times-circle comments__quote-remove pointer" onclick = "hideQuote(this)"></div>
                </a>

                <div class="publisher-box__header" id="post-textarea">
                    <textarea id = "id_textarea" class="publisher postText" name="postText" cols="10" rows="3" placeholder="<?= \Yii::t('common' , 'LABEL_WRITE_COMMENT_AND_SEND'); ?>" dir="auto" autocomplete="off" onclick="showPublisherBoxComments(this);" style="height:42px;" data-emojiable="true"></textarea>
                </div>

                <div class="publisher-box__footer hide">
                    <ul class="publisher-box__footer-list">
                        <li class="publisher-box__footer-list-item"><span id="publisher-box__smile-button"></span></li>
                    </ul>
                    <button onclick="createNewComment(this);" class="btn-invite" type="button"><span><?= \Yii::t('common' , 'LABEL_SEND'); ?></span></button>
                </div>
            </form>

        </div>

    <?php endif;?>

</div>

