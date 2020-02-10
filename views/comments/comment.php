<li indx_comment = "<?=$data['indx_comment']?>" class="comments__item <?php if ($data['fl_show_action']) echo 'bg-anim'?>" >
    <img class="comments__avatar" src="<?=$data['user_data']['path_avatar']?>" alt="avatar">
    <div class="comments__body">
        <a id = "id_ns_master_comment" class="comments__user" href=""><?=$data['user_data']['name'].' '.$data['user_data']['surname']?></a>

        <?php if ($data['fl_quote']):?>
            <a class="comments__quote" href="javascript:void(0)" indx_quote = "<?=$data['indx_comment_quote']?>" onclick = "goToComment(this, <?=$data['indx_comment_quote']?>)">
                <div class="comments__user" href=""><?=$data['master_quote_data']['name'].' '.$data['master_quote_data']['surname']?></div>
                <div class="comments__text-wrap">
                    <p class="comments__text"><?=$data['quote_message']?></p>
                </div>
            </a>
        <?php endif;?>

        <div class="comments__text-wrap">
            <p id = "id_message" class="comments__text"><?=$data['message']?></p>
        </div>

        <div class="comments__more-btn comments__more-btn--more pointer" onclick="moreBtn(this);" > <?= \Yii::t('common' , 'LABEL_READ_NEXT'); ?></div>
        <div class="comments__more-btn comments__more-btn--less pointer" onclick="moreBtn(this);"> <?= \Yii::t('common' , 'LABEL_CLOSE_TEXT'); ?></div>

        <div class="comments__footer">
            <span class="comments__date"><a class="post-time"><?=$data['date_create']?></a></span>
            <?php if ($data['can_answer_comment']):?>
                <a class="comments__answer-btn" href="javascript:void(0)" title="<?= \Yii::t('common' , 'LABEL_ANSWER_USER'); ?>" onclick = "answerComment(this, '<?=$data['indx_comment']?>')"><?= \Yii::t('common' , 'LABEL_ANSWER_USER'); ?></a>
            <?php endif;?>
        </div>

    </div>

    <?php if ($data['can_delete_comment']):?>
        <div class="comments__delete pointer" onclick = "deleteThisComment(this, <?=$data['indx_comment']?>)"><i class="far fa-trash-alt comments__delete-icon"></i></div>
    <?php endif;?>

    <?php if ($data['is_my_like']):?>
        <div class="comments__like pointer" onclick = "switchCommentLike(this, <?=$data['indx_comment']?>)" >
            <i class="fas fa-heart comments__like-icon comments__like-icon--active"></i>
            <span><?=$data['number_like']?></span>
        </div>
    <?php else:?>
        <div class="comments__like pointer" onclick = "switchCommentLike(this, <?=$data['indx_comment']?>)" >
            <i class="fas fa-heart comments__like-icon"></i>
            <span><?=$data['number_like']?></span>
        </div>
    <?php endif;?>
</li>
