<?php

require_once __DIR__ . '/OverDue.class.php';

//提前20分钟到店提醒
$overDue = new OverDue();
$overNoticeRes = $overDue->comeRemind();