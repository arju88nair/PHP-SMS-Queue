<?php
/**
 * Written by nar, 13/7/18 3:34 PM
 */

include_once 'queue_model.php';

class API
{

    private $db;
    private $queue;

    public function __construct($db_connection)
    {

        $this->db = $db_connection;
        $this->queue = new Queue($db_connection);
    }

    public function getAllQueue($params = "")
    {


        // query products
        $stmt = $this->queue->getQueues($params);
        $num = $stmt->rowCount();

        // check if more than 0 record found
        if ($num > 0) {
            // queue array
            $queue_arr = [];
            $queue_arr["status"] = "success";
            $queue_arr["data"] = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // extract row
                // this will make $row['name'] to
                // just $name only
                extract($row);

                $queue_item = array(
                    "id" => $id,
                    "body" => $body,
                    "queuedDate" => $queuedDate,
                );

                @array_push($queue_arr["data"], $queue_item);
            }
            echo json_encode($queue_arr);
        } else {
            echo json_encode(
                [
                    "status" => "success",
                    "data" => []
                ]
            );
        }
    }

    /**
     * To do the worker methods
     * @param string $params
     */
    public function queueCheck($params = "")
    {

        // query products
        $stmt = $this->queue->getOne($params);
        $num = $stmt->rowCount();

        // check if more than 0 record found
        if ($num > 0) {
            // queue array
            $queue_arr = [];
            $queue_arr["data"] = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // extract row
                // this will make $row['name'] to
                // just $name only
                extract($row);

                $queue_item = array(
                    "id" => $id,
                    "body" => $body,
                    "queuedDate" => $queuedDate,
                );

            }

            $this->SMSController($queue_item);
        } else {
            echo json_encode(
                [
                    "status" => "success",
                    "data" => []
                ]
            );
        }
    }


    public function create()
    {
        // get posted data
        $data = file_get_contents("php://input");

        //Check if is JSON
        if (!$this->isJSON($data)) {
            $this->responseError("Error, Data Format");
            exit();
        }

        $data = json_decode($data);

        //ValidATE DATE
        $message = "";
        if (!$this->validateData($data, $message)) {
            $message = "Error, Validate data. " . $message;
            $this->responseError($message);
            exit();
        }



        // set queue property values
        $this->queue->body = $data->body;
        $this->queue->queuedDate = date('Y-m-d H:i:s');

        // create the product
        if ($this->queue->create()) {
            $queue_item = [
                "id" => $this->queue->id,
                "body" => $this->queue->body,
                "queuedDate" => $this->queue->queuedDate,
            ];
            echo json_encode(
                [
                    "status" => "success",
                    "data" => $queue_item
                ]
            );
        } else {
            $this->responseError("Error create Data.");
        }
    }

    private function responseError($message)
    {
        echo json_encode(
            [
                "status" => "error",
                "message" => $message
            ]
        );
    }

    public function sendSMS()
    {

    }

    public function unQueue($params)
    {

        $this->queue->removeOne();
    }

    private function isJSON($jsonString)
    {
        return is_string($jsonString) && is_array(json_decode($jsonString, true))
        && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    private function validateData($data, &$message)
    {
        $message = "";

        //Check if Body is empty or not
        if ($data->body === "") {
            $message = "Body can't be empty";
        }
        return ($message == "");
    }

    private function SMSController($data)
    {
        // Do the mock dumping of SMS data to the log
        $body=$data['body'];
        error_log(print_r($body, true), 3, 'logs/sms.log');

        $this->removeQueue($data['id']);

    }

    private function removeQueue($id)
    {
       $flag= $this->queue->removeOne($id);
       if($flag)
       {
           error_log(print_r("Successfully removed", true), 3, 'logs/success.log');
       }
       else{
           error_log(print_r("Something went wrong", true), 3, 'logs/error.log');

       }
    }
}
