<?php
/**
 * Where the controller methods are
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

    /*
     * For developer purposes
     */
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
                    "smsfrom" => $smsfrom,
                    "smsto" => $smsto,
                    "udh" => $udh,
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
     * The queue daemon where it is ran on cron job for every second to call the SMS api
     * @param string $params
     */
    public function queueCheck()
    {

        // query products
        $stmt = $this->queue->getOne();
        $num = $stmt->rowCount();

        // check if more than 0 record found
        if ($num > 0) {
            // queue array
            $queue_arr = [];
            $queue_arr["data"] = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $queue_item = array(
                    "id" => $id,
                    "body" => $body,
                    "smsfrom" => $smsfrom,
                    "smsto" => $smsto,
                    "udh" => $udh,
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

    /*
     * The main API where the SMS object is getting inserted to the queue
     */

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

        $udh="false";
        if (strlen($data->body)>160) {
            $udh="true";
        }
        $data->udh=$udh;

        //Validate Data
        $message = "";
        if (!$this->validateData($data, $message)) {
            $message = "Error, Validate data. " . $message;
            $this->responseError($message);
            exit();
        }

        // set queue property values
        $this->queue->body = $data->body;
        $this->queue->smsfrom = $data->smsfrom;
        $this->queue->smsto = $data->smsto;
        $this->queue->udh = $data->udh;
        $this->queue->queuedDate = date('Y-m-d H:i:s');

        // create the product
        if ($this->queue->create()) {
            $queue_item = [
                "id" => $this->queue->id,
                "body" => $this->queue->body,
                "smsfrom" => $this->queue->smsfrom,
                "smsto" => $this->queue->smsto,
                "queuedDate" => $this->queue->queuedDate,
            ];
            echo json_encode(
                [
                    "status" => "Successfully sent",
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

    private function isJSON($jsonString)
    {
        return is_string($jsonString) && is_array(json_decode($jsonString, true))
        && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    private function validateData($data, &$message)
    {
        $message = "";

        //Check if Body is empty or not
        if ($data->body === "" || !isset($data->body) ) {
            $message = "Body can't be empty";
        }

        //Check if From is empty or not
        if ($data->smsfrom === "" || !isset($data->smsfrom)) {
            $message = "From Number can't be empty";
        }
        //Check if TO is empty or not
        if ($data->smsto === "" || !isset($data->smsto)) {
            $message = "To Number can't be empty";
        }

        //Check if From is number or not
        if (!is_numeric($data->smsfrom)) {
            $message = "From Number is not of proper format";
        }
        //Check if To is number or not
        if (!is_numeric($data->smsto)) {
            $message = "To Number is not of proper format";
        }
        return ($message == "");
    }

    private function SMSController($data)
    {
        // Do the mock dumping of SMS data to the log
        $body=$data['body'];
        if($data['udh'] === 'true')
        {
                // splitting the body for 160 characters
                $body=str_split($body,160);
                $i=0;
                // dummy base UDH header
                $baseUdh="06 01 03 DD ".sprintf("%02d", count($body));
                foreach ($body as $item)
                {
                    $i++;
                    $udh=$baseUdh." ".sprintf("%02d", $i);
                    $data['body']=$udh.$item;
                    error_log(print_r((array)$data,true),3,'logs/sms.log');


                }
            return false;
        }
        // For single message part
        $data['body']='05 00 03 CC 01 01 '.$body;
        error_log(print_r((array)$data,true),3,'logs/sms.log');

        $this->removeQueue($data['id']);

    }

    /*
     * To un queue the entry
     */
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
