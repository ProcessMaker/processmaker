<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Application;

class ApplicationTransformer extends TransformerAbstract
{
    public function transform(Application $application)
    {
        return [

            'app_create_date' => $application->APP_CREATE_DATE,
            'app_current_user' => $application->APP_CUR_USER,
            'app_del_previous_user' => $application->APP_UID,
            'app_finish_date' => $application->APP_FINISH_DATE,
            'app_number' => $application->APP_NUMBER,
            'app_overdue_percentage' => 0,
            'app_pro_title' => $application->APP_TITLE,
            'app_status_label' => $application->APP_STATUS,
            'app_status' => $application->APP_STATUS_ID,
            'app_tas_title' => $application->process->task->TAS_TITLE,
            'app_thread_status' => $application->threads->APP_THREAD_STATUS,
            'app_title' => $application->APP_TITLE,
            'app_uid' => $application->APP_UID,
            'app_update_date' => $application->APP_UPDATE_DATE,

            'del_delay_duration' => $application->delegation->DEL_DELAY_DURATION,
            'del_delayed' => $application->delegation->DEL_DELAYED,
            'del_delegate_date' => $application->delegation->DEL_DELEGATE_DATE,
            'del_duration' => $application->delegation->DEL_DURATION,
            'del_finish_date' => $application->delegation->DEL_FINISH_DATE,
            'del_finished' => $application->delegation->DEL_FINISH,
            'del_index' => $application->delegation->DEL_INDEX,
            'del_init_date' => $application->delegation->DEL_INIT_DATE,
            'del_last_index' => $application->delegation->DEL_FINISHED,
            'del_priority' => $application->delegation->DEL_PRIORITY,
            'del_queue_duration' => $application->delegation->DEL_QUEUE_DURATION,
            'del_started' => $application->delegation->DEL_STARTED,
            'del_task_due_date' => $application->delegation->DEL_TASK_DUE_DATE,
            'del_thread_status' => $application->delegation->DEL_THREAD_STATUS,

            'previous_usr_uid' => $application->APP_INIT_USER,
            'pro_uid' => $application->PRO_UID,
            'tas_uid' => $application->process->task->TAS_ID,

            'usr_firstname' => $application->user->USR_FIRSTNAME,
            'usr_lastname' => $application->user->USR_LASTNAME,
            'usr_uid' => $application->USR_UID,
            'usr_username' => $application->user->USR_USERNAME,

            'usrcr_usr_firstname' => $application->user->USR_FIRSTNAME,
            'usrcr_usr_lastname' => $application->user->USR_LASTNAME,
            'usrcr_usr_uid' => $application->user->USR_UID,
            'usrcr_usr_username' => $application->user->USR_USERNAME,

            'appcvcr_app_tas_title' => $application->process->task->TAS_TITLE,

        ];
    }
}
