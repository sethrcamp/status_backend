<?php

require_once __DIR__."/../models/slack.php";

class CommandsController {

    public static function iam($request, $response, $args) {

        $body = $request->getParsedBody();
        $user = Users::getByName($body['user_name']);
        $user_status = UserStatus::getById(intval($user['id']));

        $words = array_values(array_filter(explode(' ', $body['text']), function($word) {
            return $word != "";
        }));
        if(sizeof($words) == 0)
            throw new Exception("You have not provided any parameters");
        $firstWord = strtolower($words[0]);

        $status = Status::getByName($firstWord)[0];

        if(!$status)
            throw new Exception("There are no statuses with that name");
        $params = [
            "user_id" => $user['id'],
            "status_id" => intval($status['id']),
            "default_status_id" => intval($status['id'])
        ];

        if(!$user_status) {
            $updated_user_status = UserStatus::create($params);
        } else {
            $updated_user_status = UserStatus::update(intval($user['id']), $params);
        }

        if($status['status'] == "free") {
            $peopleToNotify = Notifications::getAllApplicable($user['id']);die("ERROR ERROR ERROR");
            Notifications::deleteAllApplicable($user['id']);

            if(sizeof($peopleToNotify) > 0) {
                foreach ($peopleToNotify as $person) {
                    $slack = new Slack();
                    $message = "@".$user['slack_handle']." is now `free`!";
                    $slack->sendMessage($message, $person['slack_handle']);
                }
            }
        }


        $both = false;
        $start_time = time();
        $end_time = null;


        if(sizeof($words) > 1) {
            $secondWord = strtolower($words[1]);
            $hours = [];
            $minutes = [];
            preg_match('~\d+(?=hrs)|\d+(?=hours)~', $secondWord, $hours);
            preg_match('~\d+(?=min)|\d+(?=minutes)~', $secondWord, $minutes);

            if(!(sizeof($minutes) == 0 && sizeof($hours) == 0)) {
                if(sizeof($hours) > 1) {
                    if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                        $message = ["text" => "Sorry, but you may only define *1* number of hours"];
                        return $response->withJson($message);
                    }
                    throw new Exception("You may only define 1 number of hours");
                }
                if(sizeof($minutes) > 1) {
                    if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                        $message = ["text" => "Sorry, but you may only define *1* number of minutes"];
                        return $response->withJson($message);
                    }
                    throw new Exception("You may only define 1 number of minutes");
                }
                if(sizeof($minutes) == 1 && sizeof($hours) == 1) {
                    $end_time = time() + intval($hours[0])*60*60 + intval($minutes[0])*60;
                } else if(sizeof($hours) == 1) {
                    $end_time = time() + intval($hours[0])*60*60;
                } else {
                    $end_time = time() + intval($minutes[0])*60;
                }
            } else {

                if(strpos($secondWord, "-") !== false) {
                    $baseTime = 0;
                    $addTime = 0;
                    preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d|\d\d\d\d)(?:@(?:[1-9]|1[012])(?:am|pm))?|(?:(?:[1-9]|1[012])(?:am|pm))|(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?:@(?:[1-9]|1[012])(?:am|pm))?)(?=-)~',
                        $secondWord,
                        $startParam
                    );
                    preg_match('~(?<=-)(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d|\d\d\d\d)(?:@(?:[1-9]|1[012])(?:am|pm))?|(?:(?:[1-9]|1[012])(?:am|pm))|(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?:@(?:[1-9]|1[012])(?:am|pm))?)~',
                        $secondWord,
                        $endParam
                    );
                    if(sizeof($startParam) != 1 || sizeof($endParam) != 1) {
                        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                            $message = ["text" => "Uh oh! Ranges must be in the format *[time]-[time]*!"];
                            return $response->withJson($message);
                        }
                        throw new Exception("Incorrect range format");
                    }
                    if(strpos($startParam[0], "@") !== false){
                        $dayArray = [];
                        $dateArray = [];
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $startParam[0], $dayArray);
                        if(sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $startParam[0], $dateArray);
                            $baseTime += strtotime($dateArray[0]);
                        } else {
                            if($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now%(24*60*60);
                                $baseTime = $now-$secondsPassedToday;
                            } else {
                                $baseTime += strtotime("next ".$dayArray[0]);
                            }
                        }
                        $timeArray = [];
                        if(strpos($startParam[0], "am") !== false) {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $startParam[0], $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                        } else {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                        }
                    } else {
                        $dayArray = [];
                        $dateArray = [];
                        $timeArray = [];
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $startParam[0], $dayArray);
                        if(sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $startParam[0], $dateArray);
                            if(sizeof($dateArray) == 0){
                                if(strpos($startParam[0], "am") !== false) {
                                    preg_match('~(?:\d\d|\d)(?=am)~', $startParam[0], $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $midnightToday = $now-$secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                                    $baseTime = $midnightToday + $hourToday;
                                } else {
                                    preg_match('~(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $midnightToday = $now-$secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                                    $baseTime = $midnightToday + $hourToday + (4*60*60);
                                }
                            } else {
                                $baseTime += strtotime($dateArray[0]);
                            }
                        } else {
                            if($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now%(24*60*60);
                                $baseTime = $now-$secondsPassedToday;
                            } else {
                                $baseTime += strtotime("next ".$dayArray[0]);
                            }
                        }
                    }
                    $start_time = $baseTime + $addTime;
                    $baseTime = 0;
                    $addTime = 0;
                    if(strpos($endParam[0], "@") !== false){
                        $dayArray = [];
                        $dateArray = [];
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $endParam[0], $dayArray);
                        if(sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $endParam[0], $dateArray);
                            $baseTime += strtotime($dateArray[0]);
                        } else {
                            if($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now%(24*60*60);
                                $baseTime = $now-$secondsPassedToday;
                            } else {
                                $baseTime += strtotime("next ".$dayArray[0]);
                            }
                        }
                        $timeArray = [];
                        if(strpos($endParam[0], "am") !== false) {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $endParam[0], $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                        } else {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                        }
                    } else {
                        $dayArray = [];
                        $dateArray = [];
                        $timeArray = [];
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $endParam[0], $dayArray);
                        if(sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $endParam[0], $dateArray);
                            if(sizeof($dateArray) == 0){
                                if(strpos($endParam[0] , "am") !== false) {
                                    preg_match('~(?:\d\d|\d)(?=am)~', $endParam[0], $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $midnightToday = $now-$secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                                    $baseTime = $midnightToday + $hourToday;
                                } else {
                                    preg_match('~(?:\d\d|\d)(?=pm)~', $endParam[0], $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $midnightToday = $now-$secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                                    $baseTime = $midnightToday + $hourToday + (4*60*60);
                                }
                            } else {
                                $baseTime += strtotime($dateArray[0]) + (24*60*60-1);
                            }
                        } else {

                            if($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now%(24*60*60);
                                $baseTime = $now-$secondsPassedToday;
                            } else {
                                $baseTime += strtotime("next ".$dayArray[0]) + (24*60*60-1);
                            }
                        }
                    }
                    $end_time = $baseTime + $addTime;
                } else {
                    $dayArray = [];
                    $dateArray = [];
                    $timeArray = [];
                    if(strpos($secondWord, "@") !== false) {
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $secondWord, $dayArray);
                        if(sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $secondWord, $dateArray);
                            $baseTime = strtotime($dateArray[0]);
                        } else {
                            if($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now%(24*60*60);
                                $baseTime = $now-$secondsPassedToday;
                            } else {
                                $baseTime = strtotime("next ".$dayArray[0]);
                            }
                        }
                        $timeArray = [];
                        if(strpos($secondWord, "am") !== false) {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $secondWord, $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                        } else {
                            preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $secondWord, $timeArray);
                            $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                        }
                        $end_time = $baseTime + $addTime;
                    } else {
                        preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $secondWord, $dayArray);
                        if (sizeof($dayArray) == 0) {
                            preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $secondWord, $dateArray);
                            if (sizeof($dateArray) == 0) {
                                if (strpos($secondWord, "am") !== false) {
                                    preg_match('~(?:\d\d|\d)(?=am)~', $secondWord, $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now % (24 * 60 * 60);
                                    $midnightToday = $now - $secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0]) * 60 * 60);
                                    $end_time = $midnightToday + $hourToday;
                                    if ($end_time < $now)
                                        $end_time += (24 * 60 * 60);
                                } else {
                                    preg_match('~(?:\d\d|\d)(?=pm)~', $secondWord, $timeArray);
                                    $now = time();
                                    $secondsPassedToday = $now % (24 * 60 * 60);
                                    $midnightToday = $now - $secondsPassedToday;
                                    $hourToday = intval($timeArray[0]) == 12 ? (12 * 60 * 60) : ((intval($timeArray[0]) + 12) * 60 * 60);
                                    $end_time = $midnightToday + $hourToday + (4 * 60 * 60);
                                    if ($end_time < $now)
                                        $end_time += (24 * 60 * 60);
                                }
                            } else {
                                $start_time = strtotime($dateArray[0]);
                                $end_time = strtotime($dateArray[0]) + (24 * 60 * 60 - 1);
                            }
                        } else {

                            if ($dayArray[0] == "today") {
                                $now = time();
                                $secondsPassedToday = $now % (24 * 60 * 60);
                                $end_time = $now - $secondsPassedToday + (24 * 60 * 60 - 1);
                            } else {
                                $start_time = strtotime("next " . $dayArray[0]);
                                $end_time = strtotime("next " . $dayArray[0]) + (24 * 60 * 60 - 1);
                            }
                        }
                    }
                }
            }
            $description = "";
            if(sizeof($words) > 2) {
                $thirdWord = $words[2];
                $descriptionArray = [];
                preg_match('~(?<=").+?(?=")~', $thirdWord, $descriptionArray);

                if(sizeof($descriptionArray) != 0) {
                    $description = $descriptionArray[0];
                } else {
                    //repeate
                }
            }

            $data = [
                'user_id' => $user['id'],
                'status_id' => $status['id'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'description' => $description
            ];

            $event = Events::create($data);

            $message = ["text" => "You have created the event '$description'"];
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w")
                return $response->withJson($message);
            return $response->withJson($event);
        }



        $message = ["text" => "Your status has been set to ".$status['prefix']."`".$firstWord."`!"];
        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w")
            return $response->withJson($message);
        return $response->withJson($updated_user_status);
    }

    public static function notifyme($request, $response, $args) {
        $body = $request->getParsedBody();
        $user = Users::getByName($body['user_name']);
        $words = array_values(array_filter(explode(' ', $body['text']), function($word) {
            return $word != "";
        }));

        if(sizeof($words) < 1 || sizeof($words) > 3){
            if (isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "I didn't quite understand that! /notifyme should only have *1 to 3* parameters (/notifyme [name] [timing])."];
                return $response->withJson($message);
            }
            throw new Exception("You may only have 1 to 3 parameters for the notifyme command");
        }

        $firstWord = $words[0];
        $from_user = Users::getByName($firstWord);
        if(!$from_user) {
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "Oops! It looks like there are no users with the slack handle \"@".$firstWord."\"!"];
                return $response->withJson($message);
            }
            throw new Exception("There is no user with the slack handle ".$firstWord);
        }

        $both = false;
        $start_time = time();
        $end_time = null;

        if(sizeof($words) > 1) {
            $secondWord = $words[1];
            if(strtolower($secondWord) == "both") {
                $both = true;
            } else {
                $hours = [];
                $minutes = [];
                preg_match('~\d+(?=hrs)|\d+(?=hours)~', $secondWord, $hours);
                preg_match('~\d+(?=min)|\d+(?=minutes)~', $secondWord, $minutes);
                if(!(sizeof($minutes) == 0 && sizeof($hours) == 0)) {
                    if(sizeof($hours) > 1) {
                        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                            $message = ["text" => "Sorry, but you may only define *1* number of hours"];
                            return $response->withJson($message);
                        }
                        throw new Exception("You may only define 1 number of hours");
                    }
                    if(sizeof($minutes) > 1) {
                        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                            $message = ["text" => "Sorry, but you may only define *1* number of minutes"];
                            return $response->withJson($message);
                        }
                        throw new Exception("You may only define 1 number of minutes");
                    }
                    if(sizeof($minutes) == 1 && sizeof($hours) == 1) {
                        $end_time = time() + intval($hours[0])*60*60 + intval($minutes[0])*60;
                    } else if(sizeof($hours) == 1) {
                        $end_time = time() + intval($hours[0])*60*60;
                    } else {
                        $end_time = time() + intval($minutes[0])*60;
                    }
                } else {
                    if(strpos($secondWord, "-") !== false) {
                        $baseTime = 0;
                        $addTime = 0;
                        preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d|\d\d\d\d)(?:@(?:[1-9]|1[012])(?:am|pm))?|(?:(?:[1-9]|1[012])(?:am|pm))|(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?:@(?:[1-9]|1[012])(?:am|pm))?)(?=-)~',
                                    $secondWord,
                                    $startParam
                        );
                        preg_match('~(?<=-)(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d|\d\d\d\d)(?:@(?:[1-9]|1[012])(?:am|pm))?|(?:(?:[1-9]|1[012])(?:am|pm))|(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?:@(?:[1-9]|1[012])(?:am|pm))?)~',
                                    $secondWord,
                                    $endParam
                        );
                        if(sizeof($startParam) != 1 || sizeof($endParam) != 1) {
                            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                                $message = ["text" => "Uh oh! Ranges must be in the format *[time]-[time]*!"];
                                return $response->withJson($message);
                            }
                            throw new Exception("Incorrect range format");
                        }
                        if(strpos($startParam[0], "@") !== false){
                            $dayArray = [];
                            $dateArray = [];
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $startParam[0], $dayArray);
                            if(sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $startParam[0], $dateArray);
                                $baseTime += strtotime($dateArray[0]);
                            } else {
                                if($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $baseTime = $now-$secondsPassedToday;
                                } else {
                                    $baseTime += strtotime("next ".$dayArray[0]);
                                }
                            }
                            $timeArray = [];
                            if(strpos($startParam[0], "am") !== false) {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $startParam[0], $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                            } else {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                            }
                        } else {
                            $dayArray = [];
                            $dateArray = [];
                            $timeArray = [];
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $startParam[0], $dayArray);
                            if(sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $startParam[0], $dateArray);
                                if(sizeof($dateArray) == 0){
                                    if(strpos($startParam[0], "am") !== false) {
                                        preg_match('~(?:\d\d|\d)(?=am)~', $startParam[0], $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now%(24*60*60);
                                        $midnightToday = $now-$secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                                        $baseTime = $midnightToday + $hourToday;
                                    } else {
                                        preg_match('~(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now%(24*60*60);
                                        $midnightToday = $now-$secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                                        $baseTime = $midnightToday + $hourToday + (4*60*60);
                                    }
                                } else {
                                    $baseTime += strtotime($dateArray[0]);
                                }
                            } else {
                                if($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $baseTime = $now-$secondsPassedToday;
                                } else {
                                    $baseTime += strtotime("next ".$dayArray[0]);
                                }
                            }
                        }
                        $start_time = $baseTime + $addTime;
                        $baseTime = 0;
                        $addTime = 0;
                        if(strpos($endParam[0], "@") !== false){
                            $dayArray = [];
                            $dateArray = [];
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $endParam[0], $dayArray);
                            if(sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $endParam[0], $dateArray);
                                $baseTime += strtotime($dateArray[0]);
                            } else {
                                if($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $baseTime = $now-$secondsPassedToday;
                                } else {
                                    $baseTime += strtotime("next ".$dayArray[0]);
                                }
                            }
                            $timeArray = [];
                            if(strpos($endParam[0], "am") !== false) {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $endParam[0], $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                            } else {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $startParam[0], $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                            }
                        } else {
                            $dayArray = [];
                            $dateArray = [];
                            $timeArray = [];
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $endParam[0], $dayArray);
                            if(sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $endParam[0], $dateArray);
                                if(sizeof($dateArray) == 0){
                                    if(strpos($endParam[0] , "am") !== false) {
                                        preg_match('~(?:\d\d|\d)(?=am)~', $endParam[0], $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now%(24*60*60);
                                        $midnightToday = $now-$secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                                        $baseTime = $midnightToday + $hourToday;
                                    } else {
                                        preg_match('~(?:\d\d|\d)(?=pm)~', $endParam[0], $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now%(24*60*60);
                                        $midnightToday = $now-$secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                                        $baseTime = $midnightToday + $hourToday + (4*60*60);
                                    }
                                } else {
                                    $baseTime += strtotime($dateArray[0]) + (24*60*60-1);
                                }
                            } else {
                                if($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $baseTime = $now-$secondsPassedToday;
                                } else {
                                    $baseTime += strtotime("next ".$dayArray[0]) + (24*60*60-1);
                                }
                            }
                        }
                        $end_time = $baseTime + $addTime;
                    } else {
                        $dayArray = [];
                        $dateArray = [];
                        $timeArray = [];
                        if(strpos($secondWord, "@") !== false) {
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)(?=@)~', $secondWord, $dayArray);
                            if(sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))(?=@)~', $secondWord, $dateArray);
                                $baseTime = strtotime($dateArray[0]);
                            } else {
                                if($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now%(24*60*60);
                                    $baseTime = $now-$secondsPassedToday;
                                } else {
                                    $baseTime = strtotime("next ".$dayArray[0]);
                                }
                            }
                            $timeArray = [];
                            if(strpos($secondWord, "am") !== false) {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=am)~', $secondWord, $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0])*60*60);
                            } else {
                                preg_match('~(?<=@)(?:\d\d|\d)(?=pm)~', $secondWord, $timeArray);
                                $addTime = intval($timeArray[0]) == 12 ? (12*60*60) : ((intval($timeArray[0])+12)*60*60);
                            }
                            $end_time = $baseTime + $addTime;
                        } else {
                            preg_match('~(?:sunday|monday|tuesday|wednesday|thursday|friday|saturday|today)~', $secondWord, $dayArray);
                            if (sizeof($dayArray) == 0) {
                                preg_match('~(?:(?:0[1-9]|[1-9]|1[012])\/(?:0[1-9]|[1-9]|[12][0-9]|3[01])\/(?:\d\d\d\d|\d\d))~', $secondWord, $dateArray);
                                if (sizeof($dateArray) == 0) {
                                    if (strpos($secondWord, "am") !== false) {
                                        preg_match('~(?:\d\d|\d)(?=am)~', $secondWord, $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now % (24 * 60 * 60);
                                        $midnightToday = $now - $secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? 0 : (intval($timeArray[0]) * 60 * 60);
                                        $end_time = $midnightToday + $hourToday;
                                        if ($end_time < $now)
                                            $end_time += (24 * 60 * 60);
                                    } else {
                                        preg_match('~(?:\d\d|\d)(?=pm)~', $secondWord, $timeArray);
                                        $now = time();
                                        $secondsPassedToday = $now % (24 * 60 * 60);
                                        $midnightToday = $now - $secondsPassedToday;
                                        $hourToday = intval($timeArray[0]) == 12 ? (12 * 60 * 60) : ((intval($timeArray[0]) + 12) * 60 * 60);
                                        $end_time = $midnightToday + $hourToday + (4 * 60 * 60);
                                        if ($end_time < $now)
                                            $end_time += (24 * 60 * 60);
                                    }
                                } else {
                                    $start_time = strtotime($dateArray[0]);
                                    $end_time += strtotime($dateArray[0]) + (24 * 60 * 60 - 1);
                                }
                            } else {
                                if ($dayArray[0] == "today") {
                                    $now = time();
                                    $secondsPassedToday = $now % (24 * 60 * 60);
                                    $end_time = $now - $secondsPassedToday + (24 * 60 * 60 - 1);
                                } else {
                                    $start_time = strtotime("next " . $dayArray[0]);
                                    $end_time += strtotime("next " . $dayArray[0]) + (24 * 60 * 60 - 1);
                                }
                            }
                        }
                    }
                }
            }
        }

        if(sizeof($words) > 2) {
            $thirdWord = $words[2];
            if($both) {
                if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                    $message = ["text" => "Sorry, \"both\" is not a valid time!"];
                    return $response->withJson($message);
                }
                throw new Exception("'Both' is not a valid time");
            }
            if(strtolower($thirdWord) == "both") {
                $both = true;
            } else {
                if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                    $message = ["text" => "Oops! The third parameter of /notify me should either be \"both\" of left blank!"];
                    return $response->withJson($message);
                }
                throw new Exception("The third parameter of notifyme should be 'both' or left blank");
            }
        }

        $data = [
            "to_user_id" => $user['id'],
            "from_user_id" => $from_user['id'],
            "start_time" => $start_time,
            "end_time" => $end_time,
            "both_users_free" => $both,
        ];

        $notification = Notifications::create($data);

        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
            $message = ["text" => "You will be notified when @".$from_user['slack_handle']." is free!"];
            return $response->withJson($message);
        }
        return $response->withJson($notification);
    }

    public static function whereis($request, $response, $args) {
        $body = $request->getParsedBody();

        $words =explode(" ", $body['text']);

        if(sizeof($words) != 1) {
            if (isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "I didn't quite understand that! /whereis should only have *1* parameter (/whereis [name])."];
                return $response->withJson($message);
            }
            throw new Exception("You may only have 1 parameter for the whereis command");
        }

        $firstWord = strtolower($words[0]);

        $from_user = Users::getByName($firstWord);
        if(!$from_user) {
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "Oops! It looks like there are no users with the slack handle \"@".$firstWord."\"!"];
                return $response->withJson($message);
            }
            throw new Exception("There is no user with the slack handle ".$firstWord);
        }

        $from_user_status = UserStatus::getById($from_user['id']);
        if(!$from_user_status) {
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "Sorry, but @".$firstWord." has not set up a status yet. (Maybe you should tell them how cool StatusBot is!)"];
                return $response->withJson($message);
            }
            throw new Exception($firstWord." has not set up a status yet.");
        }

        $status = Status::getById($from_user_status['status_id']);

        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
            $message = ["text" => "@".$firstWord." is currently ".$status['prefix']."`".$status['status']."`!"];
            return $response->withJson($message);
        }
        return $response->withJson($from_user_status);
    }

}