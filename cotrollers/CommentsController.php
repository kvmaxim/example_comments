<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 08.10.18
 * Time: 10:26
 */

namespace app\controllers;

use app\models\DataBase;
use app\models\Permissions;
use app\models\User;
use app\models\Comments;
use yii\web\Controller;
use Yii;

class CommentsController extends Controller
{
    public function actionGetBlockComments()
    {
        $data_user = \yii\helpers\Json::decode(\Yii::$app->request->post('data'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_comments = $data_user['id_comments'];
        $place_creation = $data_user['place_creation'];

        $id_current_user = User::getIdCurrentUser();

        if (Permissions::checkCanUserGetCommentsFromPost($id_current_user, $id_comments) === false) return;

        $data_user = User::getDataCurrentUser();

        $model_comments = new Comments();
        $data_comments = $model_comments->getComments($id_comments);

        $list_up_comments = "";

        $can_create_comment = true;

        if (User::getIdCurrentUser() === null) $can_create_comment = false;

        foreach($data_comments['up_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_up_comments = $comment.' '.$list_up_comments;
        }

        $number_comments = $data_comments['number_comments'];//DataBase::getInstance()->tbCommentsGetNumberComments($id_post);

        $remaining_comments = $number_comments - \app\models\COUNT_COMMENTS_IN_REQUEST;

        $data_for_block_comments = [
            'remaining_comments' => $remaining_comments,
            'user_path_avatar' => $data_user['path_avatar'],
            'id_comments' =>$id_comments,
            'list_comments' => $list_up_comments,
            'indx_down_comment' => $data_comments['indx_down_comment'],
            'indx_up_comment' => $data_comments['indx_up_comment'],
            'can_create_comment' => $can_create_comment,
            'place_creation' => $place_creation,
        ];

        $block_comments = $this->renderPartial('/comments/block_comments',[
            'data' =>$data_for_block_comments,
        ]);

        return [
            'block_comments' => $block_comments,
            'id_comments' => $id_comments,
            'number_comments' => $number_comments,
        ];
    }

    public function actionGetComments()
    {
        $data = \yii\helpers\Json::decode(\Yii::$app->request->post('data'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_current_user = User::getIdCurrentUser();

        $model_comments = new Comments();
        $data_comments = $model_comments->getComments($data['id_comments'], $data['indx_up_comment'], $data['indx_down_comment']);

        $list_new_comments_up = "";
        $list_new_comments_down = "";

        $can_create_comment = true;

        if (User::getIdCurrentUser() === null) $can_create_comment = false;

        foreach($data_comments['up_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_new_comments_up = $comment.' '.$list_new_comments_up;
        }

        foreach($data_comments['down_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_new_comments_down = $list_new_comments_down.' '.$comment;
        }

        return [
            'list_new_comments_up' => $list_new_comments_up,
            'list_new_comments_down' => $list_new_comments_down,
            'id_comments' => $data['id_comments'],
            'number_comments' => $data_comments['number_comments'],
            'number_comments_in_up_block' => $data_comments['up_block']['number_comments_in_list'],
            'indx_down_comment' => $data_comments['indx_down_comment'],
            'indx_up_comment' => $data_comments['indx_up_comment'],
        ];
    }

    public function actionCreateComment()
    {
        $data = \yii\helpers\Json::decode(\Yii::$app->request->post('data'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $tmp = $data['place_creation'];

        $model_comments = new Comments();
        $data_new_comments = $model_comments->createComment($data);

        $list_new_comments = "";

        $can_create_comment = true;

        if (User::getIdCurrentUser() === null) $can_create_comment = false;

        if (is_array($data_new_comments))
        {
            foreach($data_new_comments as $key => $data_comment)
            {
                if (!is_array($data_comment)) continue;

                $data_comment['fl_quote'] = false;
                if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

                $data_comment['fl_show_action'] = true;

                if ($can_create_comment === false)
                {
                    $data_comment['can_delete_comment'] = false;
                    $data_comment['can_answer_comment'] = false;
                } else {
                    $data_comment['can_delete_comment'] = true;
                    $data_comment['can_answer_comment'] = true;
                }

                $comment = $this->renderPartial('/comments/comment',[
                    'data' => $data_comment,
                ]);

                $list_new_comments = $comment.' '.$list_new_comments;
            }
        }

        return [
            'list_new_comments' => $list_new_comments,
            'id_comments' => $data['id_comments'],
            'number_comments' => $data_new_comments['number_comments'],
            'indx_down_comment' => $data_new_comments['indx_down_comment'],
        ];
    }

    public function actionDeleteComment()
    {
        $id_current_user = User::getIdCurrentUser();
        $data_user = \yii\helpers\Json::decode(\Yii::$app->request->post('data'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_comments = $data_user['id_comments'];
        $indx_comment = $data_user['indx_comment'];

        $model_comments = new Comments();
        $result = $model_comments->deleteComment($id_current_user, $id_comments, $indx_comment);

        if (!$result) return;

        return [
            'indx_comment' => $indx_comment,
            'id_comments' => $id_comments,
        ];
    }

    public function actionGoToComment()
    {
        $data = \yii\helpers\Json::decode(\Yii::$app->request->post('data'));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_current_user = User::getIdCurrentUser();

        $number_up_comments = \app\models\COUNT_COMMENTS_IN_REQUEST;
        if (!isset($data['indx_up_comment'])) {
            $data['indx_up_comment'] = 0;
        } else $number_up_comments = $data['indx_up_comment'] - $data['indx_quote'];

        $model_comments = new Comments();
        $data_comments = $model_comments->getComments($data['id_comments'], $data['indx_up_comment'], $data['indx_down_comment'], $number_up_comments);

        $list_new_comments_up = "";
        $list_new_comments_down = "";

        $can_create_comment = true;

        if (User::getIdCurrentUser() === null) $can_create_comment = false;
        $fl_has_comment_for_quote = false;

        foreach($data_comments['up_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;
            if ($data_comment['indx_comment'] == $data['indx_quote']) {
                $fl_has_comment_for_quote = true;
                $data_comment['fl_show_action'] = true;
            }

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_new_comments_up = $comment.' '.$list_new_comments_up;
        }

        foreach($data_comments['down_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_new_comments_down = $list_new_comments_down.' '.$comment;
        }

        return [
            'fl_has_comment_for_quote' => $fl_has_comment_for_quote,
            'message_if_comment_not_exist' => \Yii::t('common' , 'LABEL_COMMENT_WAS_DELETED'),
            'list_new_comments_up' => $list_new_comments_up,
            'list_new_comments_down' => $list_new_comments_down,
            'id_comments' => $data['id_comments'],
            'number_comments' => $data_comments['number_comments'],
            'number_comments_in_up_block' => $data_comments['up_block']['number_comments_in_list'],
            'indx_down_comment' => $data_comments['indx_down_comment'],
            'indx_up_comment' => $data_comments['indx_up_comment'],
        ];

    }

    public function actionGetBlockCommentsAndGoToComment()
    {
        $data = json_decode(\Yii::$app->request->post('data'), true);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_comments = $data['id_comments'];
        $indx_comment = $data['indx_comment'];
        $place_creation = $data['place_creation'];
        $id_current_user = User::getIdCurrentUser();

        $data_user = User::getDataCurrentUser();

        $model_comments = new Comments();
        $number_elems_to_end = $model_comments->getNumberElemsToEnd($id_comments, $indx_comment) + 2;

        $data_comments = $model_comments->getComments($id_comments, -1, -1, $number_elems_to_end);

        $list_up_comments = "";

        $can_create_comment = true;

        if (User::getIdCurrentUser() === null) $can_create_comment = false;
        $fl_exist_comment = false;

        foreach($data_comments['up_block'] as $data_comment)
        {
            if (!is_array($data_comment)) continue;

            $data_comment['fl_quote'] = false;
            if (strlen($data_comment['quote_message']) > 0) $data_comment['fl_quote'] = true;

            $data_comment['fl_show_action'] = false;
            if ($data_comment['indx_comment'] == $data['indx_comment']) {
                $fl_exist_comment= true;
                $data_comment['fl_show_action'] = true;
            }

            $data_comment['can_answer_comment'] = $can_create_comment;

            $data_comment['can_delete_comment'] = false;
            if ($id_current_user == $data_comment['id_user']) $data_comment['can_delete_comment'] = true;

            $comment = $this->renderPartial('/comments/comment',[
                'data' => $data_comment,
            ]);

            $list_up_comments = $comment.' '.$list_up_comments;
        }

        $number_comments = $data_comments['number_comments'];//DataBase::getInstance()->tbCommentsGetNumberComments($id_post);

        $remaining_comments = $number_comments - $number_elems_to_end;

        $data_for_block_comments = [
            'remaining_comments' => $remaining_comments,
            'user_path_avatar' => $data_user['path_avatar'],
            'id_comments' =>$id_comments,
            'list_comments' => $list_up_comments,
            'indx_down_comment' => $data_comments['indx_down_comment'],
            'indx_up_comment' => $data_comments['indx_up_comment'],
            'can_create_comment' => $can_create_comment,
            'place_creation' => $place_creation,
        ];

        $block_comments = $this->renderPartial('/comments/block_comments',[
            'data' =>$data_for_block_comments,
        ]);

        return [
            'fl_exist_comment' => $fl_exist_comment,
            'message_if_comment_not_exist' => \Yii::t('common' , 'LABEL_COMMENT_WAS_DELETED'),
            'block_comments' => $block_comments,
            'id_comments' => $id_comments,
            'number_comments' => $number_comments,
            'indx_comment' => $indx_comment,
        ];
    }

    public function actionDeleteLikeFromComment()
    {
        $data = json_decode(\Yii::$app->request->post('data'), true);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_comments = $data['id_comments'];
        $indx_comment = $data['indx_comment'];

        $model_comments = new Comments();
        $result_status = $model_comments->deleteLikeFromComment($id_comments, $indx_comment);

        return [
            'status' => $result_status,
        ];
    }

    public function actionAddLikeToComment()
    {
        $data = json_decode(\Yii::$app->request->post('data'), true);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $id_comments = $data['id_comments'];
        $indx_comment = $data['indx_comment'];

        $model_comments = new Comments();
        $result_status = $model_comments->addLikeToComment($id_comments, $indx_comment);

        return [
            'status' => $result_status,
        ];
    }
}
