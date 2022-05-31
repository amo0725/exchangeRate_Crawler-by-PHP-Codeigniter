<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GetCur extends CI_Controller {
	public function __construct(){
        parent::__construct();
        $this->load->database();
        unset($this->session);
    }

    public function Portal(){
        $handle = fopen("https://portal.sw.nat.gov.tw/APGQ/GC331!downLoad?formBean.downLoadFile=CURRENT_JSON","rb");
        $content = "";
        while (!feof($handle)) {
            $content .= fread($handle, 10000);
        }
        fclose($handle);
        $content = json_decode($content,true);
        $data = array();
        $dateTime = new DateTime();
        $dateTime->sub(new DateInterval('P1D'));
        $dateTime = $dateTime->format("Y-m-d");
        foreach($content['items'] as $v){
            $tmpData = array();
            $tmpData['cur_Id'] = $v['code'];
            $tmpData['cur_NowIn'] = $v['buyValue'];
            $tmpData['cur_NowOut'] = $v['sellValue'];
            $tmpData['cur_Date'] = $dateTime;
            $tmpData['cur_Source'] = 'Portal'; 
            $data[] = $tmpData;
        }
        $array = array('cur_Date' => $dateTime, 'cur_Source' => 'Portal');
        $query= $this->db->from('currency')->where($array)->get();
        if($query->result_array()){
            echo 'Data already existed !';
        }else{
            $this->db->insert_batch('currency', $data);
            print_r($data);
        }
    }

    public function TwBank(){
        $data = array();
        $dateTime = new DateTime();
        $dateTime->sub(new DateInterval('P1D'));
        $dateTime = $dateTime->format("Y-m-d");
        
        $f = fopen('php://temp', 'w+');
        $ch = curl_init('https://rate.bot.com.tw/xrt/flcsv/0/'.$dateTime);
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0");
        curl_exec($ch);
        curl_close($ch);
        rewind($f);

        while (($result = fgetcsv($f, 10000)) !== false ) {
            $content[] = $result;
        }

        fclose($f);
        
        foreach($content as $k => $v){
            if($k != 0){
                $tmpData = array();
                $tmpData['cur_Id'] = $v[0];
                $tmpData['cur_CashIn'] = $v[2]!='0.00000'?$v[2]:null;
                $tmpData['cur_NowIn'] = $v[3]!='0.00000'?$v[3]:null;
                $tmpData['cur_CashOut'] = $v[12]!='0.00000'?$v[12]:null;
                $tmpData['cur_NowOut'] = $v[13]!='0.00000'?$v[13]:null;
                $tmpData['cur_Date'] = $dateTime;
                $tmpData['cur_Source'] = 'TwBank';
                $data[] = $tmpData;
            }
        }

        $array = array('cur_Date' => $dateTime, 'cur_Source' => 'TwBank');
        $query= $this->db->from('currency')->where($array)->get();
        if($query->result_array() || empty($data)){
            echo 'Data already existed !';
        }else{
            $this->db->insert_batch('currency', $data);
            print_r($data);
        }
    }
}