<?php

namespace app\models;

use app\models\DataBase;
use app\models\science\Course;
use app\models\science\QuestionsAndAnswers;
use app\models\User;


class Comments {

    private function convertMapToArray($data)
    {
        $result = [];
        $indx_last_comment = null;
        $count = 0;

        foreach($data as $value)
        {
            $tmp = explode(':{', $value);
            if (count($tmp) != 2) continue;

            $indx_comment = intval($tmp[0]);
            $comment = json_decode('{'.$tmp[1], true);
            $comment['user_data'] = User::getDataUser($comment['id_user']);
            if ($comment['id_master_quote'] !== null && $comment['id_master_quote'] !== "") $comment['master_quote_data'] = User::getDataUser($comment['id_master_quote']);

            $comment['indx_comment'] = $indx_comment;
            $result[] = $comment;
            $indx_last_comment = $indx_comment;
            $count++;
        }

        $result['indx_last_comment'] = $indx_last_comment;
        $result['number_comments_in_list'] = $count;
        return $result;

    }

    private function addLikeDataToArray($id_content, $id_user, $data)
    {
        foreach($data as $key=>$value)
        {
            if (is_array($value))
            {
                $id_like = $id_content.$value['indx_comment'];
                $data[$key]['is_my_like'] = DataBase::getInstance()->tbLikesForTableCommentsCheckLikeInContent($id_like, $id_user);
                $data[$key]['number_like'] = DataBase::getInstance()->tbLikesForTableCommentsGetNumberLikes($id_like, $id_user);
            }
        }

        return $data;
    }

    public function getComments($id_content, $indx_up_elem = -1, $indx_down_elem = -1, $count_elems = \app\models\COUNT_COMMENTS_IN_REQUEST)
    {
        $pointer_last_elem = DataBase::getInstance()->tbCommentsGetPointerOnLastElem($id_content);
        if ($pointer_last_elem === null) {
            return [
                'up_block' => [
                    'indx_last_comments' => 0,
                    'number_comments_in_list' => 0,
                ],
                'down_block' => [],
                'number_comments' => 0,
                'indx_down_comment' => 0,
                'indx_up_comment' => 0,
            ];
        }

        if ($indx_up_elem == -1) $indx_up_elem = $pointer_last_elem; else {
            $indx_up_elem--;
            if ($indx_up_elem ==0) return false;
        }

        $id_current_user = User::getIdCurrentUser();

        $comments = DataBase::getInstance()->tbCommentsGetComments($id_content, $indx_up_elem, $count_elems);
        $comments_result['up_block'] = $this->convertMapToArray($comments);
        $comments_result['up_block'] = $this->addLikeDataToArray($id_content, $id_current_user, $comments_result['up_block']);

        $comments_result['down_block'] = [];
        if ($indx_down_elem != -1 && $indx_down_elem != $pointer_last_elem) {
            $amount_new_comments = $pointer_last_elem - $indx_down_elem;
            $new_comments = DataBase::getInstance()->tbCommentsGetComments($id_content, $pointer_last_elem, $amount_new_comments);
            $comments_result['down_block'] = $this->convertMapToArray($new_comments);
            $comments_result['down_block'] = $this->addLikeDataToArray($id_content, $id_current_user, $comments_result['down_block']);
        }

        $comments_result['number_comments'] = DataBase::getInstance()->tbCommentsGetNumberComments($id_content);
        $comments_result['indx_down_comment'] = $pointer_last_elem;
        $comments_result['indx_up_comment'] = $comments_result['up_block']['indx_last_comment'];

        return $comments_result;
    }

    public function deleteComment($id_user, $id_comments, $indx_comment)
    {
        $data = DataBase::getInstance()->tbCommentsGetComments($id_comments, $indx_comment, 1);
        $data = $this->convertMapToArray($data);

        if ($id_user != $data[0]['id_user'] || $indx_comment != $data[0]['indx_comment']) return false;

        DataBase::getInstance()->tbCommentsDeleteComment($id_comments, $indx_comment);

        return true;
    }

    public function createComment($data)
    {
        $id_current_user = User::getIdCurrentUser();

        $pointer_last_comment = DataBase::getInstance()->tbCommentsGetPointerOnLastElem($data['id_comments']);

        $quote_message = '';
        $id_master_quote = '';
        $id_set_likes = $data['id_comments'].($pointer_last_comment+1);

        if ($data['indx_comment_quote'] != -1)
        {
            $data_for_quote = DataBase::getInstance()->tbCommentsGetComments($data['id_comments'], $data['indx_comment_quote'], 1);
            $data_for_quote = $this->convertMapToArray($data_for_quote);
            $data_for_quote = $this->addLikeDataToArray($data['id_comments'], $id_current_user, $data_for_quote);

            $quote_message = $data_for_quote[0]['message'];
            $id_master_quote = $data_for_quote[0]['id_user'];

            $str_length = strlen($quote_message);
            if ($str_length > 100) $str_length = 100;
            $quote_message = substr($quote_message,0,$str_length);
        }

        $data_comment = [
            'message' => $data['message'],
            'indx_comment_quote' => $data['indx_comment_quote'],
            'quote_message' => $quote_message,
            'id_user' => $id_current_user,
            'id_master_quote' => $id_master_quote,
            'id_set_likes' => $id_set_likes,
            'date_create' => (int)microtime(true),
        ];

        $data_comment_json = json_encode($data_comment);
        DataBase::getInstance()->tbCommentsAddNewComment($data['id_comments'], $data_comment_json);

        $number_comments = DataBase::getInstance()->tbCommentsGetNumberComments($data['id_comments']);

        $count = ($pointer_last_comment + 1) - $data['indx_down_comment'];
        $comments = DataBase::getInstance()->tbCommentsGetComments($data['id_comments'], $pointer_last_comment + 1, $count);

        $comments_result = $this->convertMapToArray($comments);
        $comments_result = $this->addLikeDataToArray($data['id_comments'], $id_current_user, $comments_result);


        $comments_result['number_comments'] = $number_comments;
        $comments_result['indx_down_comment'] = $pointer_last_comment + 1;

        $model_notifications = new Notifications();

        if ($data['place_creation'] === 'course_ads')
        {
            // создаем уведомление автору курса, что появился ответ на его объявление, если только не он его написал.
            $model_s_course = new Course();
            $course_info = $model_s_course->getMinInfoCourse($data['id_course']);

            if ($id_current_user !== $course_info['id_master']) {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInCourseAd($id_current_user, $data['id_course'], $data['indx_ad'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($course_info['id_master'], $notification);
                Trace::write($result, $id_current_user);
            }

            //создаем уведомление автору комментария в объявлении, если был ответ на комментарий, который он создал.(ответ(цитата) на комментарий пользователя).
            if ($data['indx_comment_quote'] != -1 && $id_master_quote !== $id_current_user)
            {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInCourseAd($id_current_user, $data['id_course'], $data['indx_ad'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($id_master_quote, $notification);
                Trace::write($result, $id_current_user);
            }
        }

        if ($data['place_creation'] === 'course_quesitons_and_answers') {
            // создаем уведомление автору курса, что появился ответ на вопрос в уроке/тесту, если только не он его написал.
            $model_s_course = new Course();
            $course_info = $model_s_course->getMinInfoCourse($data['id_course']);

            if ($id_current_user !== $course_info['id_master'] && $id_master_quote !== $id_current_user) {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInCourseQuestionAndAnswers($id_current_user, $data['id_course'], $data['id_lesson'], $data['indx_question'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($course_info['id_master'], $notification);
                Trace::write($result, $id_current_user);
            }

            //создаем уведомление автору цитаты в комментарии в вопросе.
            if ($data['indx_comment_quote'] != -1 && $id_master_quote !== $id_current_user)
            {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInCourseQuestionAndAnswers($id_current_user, $data['id_course'], $data['id_lesson'], $data['indx_question'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($id_master_quote, $notification);
                Trace::write($result, $id_current_user);
            }

            // создаем уведомление автору вопроса в курсе к данному уроку/тесту.
            $model_questions_and_answers = new QuestionsAndAnswers();
            $data_qa = $model_questions_and_answers->getQuestionAndAnswersForLesson($data['id_course'], $data['id_lesson'], $data['indx_question']);
            $id_master_question_in_lesson = $data_qa['id_user'];

            if ($id_master_question_in_lesson != $course_info['id_master'])
            {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInCourseQuestionAndAnswers($id_current_user, $data['id_course'], $data['id_lesson'], $data['indx_question'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($id_master_question_in_lesson, $notification);
                Trace::write($result, $id_current_user);
            }

        }

        if ($data['place_creation'] === 'post')
        {
            $id_master_post = DataBase::getInstance()->tbPostGetMasterPost($data['id_comments']);

            if ($id_master_post !== $id_current_user ) {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInPost($id_current_user, $data['id_comments'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($id_master_post, $notification);
                Trace::write($result, $id_current_user);
            }

            //создаем уведомление автору цитаты в комментарии в посте.
            if ($data['indx_comment_quote'] != -1 && $id_master_quote !== $id_current_user)
            {
                $notification = $model_notifications->createTextNotificationUserWriteCommentInPost($id_current_user, $data['id_comments'], $pointer_last_comment + 1, $data['message']);
                $result = $model_notifications->sentUserNotification($id_master_quote, $notification);
                Trace::write($result, $id_current_user);
            }

        }

        return $comments_result;
    }

    public function getNumberElemsToEnd($id_comments, $indx_comment)
    {
        $pointer_last_elem = DataBase::getInstance()->tbCommentsGetPointerOnLastElem($id_comments);
        $result = $pointer_last_elem - $indx_comment;
        if ($result <0) $result = 0;

        return $result;
    }

    public function deleteLikeFromComment($id_comments, $indx_comment)
    {
        $id_current_user = User::getIdCurrentUser();
        $id_like = $id_comments.$indx_comment; // id_comment
        DataBase::getInstance()->tbLikesForTableCommentsDeleteLikeFromContent($id_like, $id_current_user);

        return STATUS_OK;
    }

    public function addLikeToComment($id_comments, $indx_comment)
    {
        $id_current_user = User::getIdCurrentUser();
        $id_like = $id_comments.$indx_comment; // id_comment
        DataBase::getInstance()->tbLikesForTableComemntsAddNewLikesInContent($id_like, $id_current_user);

        return STATUS_OK;
    }

}
