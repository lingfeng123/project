<?php

require_once __DIR__ . '/OverDue.class.php';

//逾期到期前7天提醒
$overDue = new OverDue();
$overNoticeRes = $overDue->overdueRemind();