<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ted extends CI_Controller {

  /*
  Takes:
    • [optionally] a length

  Returns:
    • A psudorandom string of $length
  */
  private function _generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }

  /*
  Takes:
    • [optionally] a length

  Returns:
    • A (hopefully) unique identifier
  */
  private function _generateUUID($length = 20) {
    $time = microtime();
    $salt = $this->_generateRandomString(20);
    $hash = md5($time . $salt);
    // If the hash string is under 20 chars, pad it
    if($hash < $length){
      $padding = $length - strlen($hash);
      $uuid = $hash . $this->_generateRandomString($padding);
    } // It it's over 20 chars, cut it down
    elseif($hash > $length){
      $cut = strlen($hash) - $length;
      $uuid = substr($hash, $cut);
    } // If it's exactly 20 chars, leave it alone
    else {
      $uuid = $hash;
    }
    return $uuid;
  }


  /*
  Takes:
    nothing

  Returns:
    A uuid
  */
  public function getuuid()
  {
    $uuid = $this->_generateUUID(20);
    $data = array(
      'uuid'=> $uuid
    );

    $this->load->view('ted/get_uuid', $data);
  }


  /*
  Takes:
    nothing
  Returns:
    A user-friendly page.
  */
  public function index()
  {
    $this->load->view('ted/ted');
  }


  /*
  Takes:
    • Gateway is the hardware identifier (Serial Number) of the unit making the request
    • Unique is the unique identifier that was supplied in step 2. This is optional and is meant
      as an extra security token to be provided to the 3rd party service if they wish to use it.

  Returns:
    • PostServer- The ip address or dns resolvable name of the server (80 character max)
    • PostPort - the port that the server is running on
    • PostURL - the path of the service receiving the post (80 character max)
    • UseSSL - whether or not to post data via SSL
    • AuthToken- Unique token provided by the 3rd party used to as an authentication
      mechanism when data is submitted by the TED5000 (optional) (20 character max)
    • PostRate - The number of minutes between data posts. (1 - 15)
    • HighPrec – Enables/disables “High Precision” mode. A value of “T” enables High
      Precision mode which will include fractional watt-hours. Any other value will floor each
      value to the nearest watt hour.

  Example input:
    <ted5000Activation>
      <Gateway>210000</Gateway>
      <Unique>123456</Unique>
    </ted5000Activation>

  Example output:
    <ted500ActivationResponse>
      <PostServer>www.theenergydetective.com</PostServer>
      <UseSSL>T</UseSSL>
      <PostPort>443</PostPort>
      <PostURL>/postdata.cgi</PostURL>
      <AuthToken>MySecurityToten</AuthToken>
      <PostRate>10</PostRate>
      <HighPrec>T</HighPrec>
    </ted500ActivationResponse>
  */
  public function activate()
  {
    // Set up response
    $this->output->set_content_type('text/xml');
    $gateway = $this->input->post('Gateway');
    $unique = $this->input->post('Unique');

    // Set up vars
    $PostServer = "http://" . $_SERVER['SERVER_NAME'];
    $PostPort = $_SERVER['SERVER_PORT'];
    $PostURL = '/ted/data';
    if (empty($_SERVER['HTTPS'])) {
      $UseSSL = "F";
    } else {
      $UseSSL = "T";
    }
    $AuthToken = $this->_generateUUID(20);
    $PostRate = "1";
    $HighPrec = "T";

    // Generate output
    $xml = new SimpleXMLElement('<ted500ActivationResponse/>');
    $xml->addChild('PostServer', $PostServer);
    $xml->addChild('UseSSL', $UseSSL);
    $xml->addChild('PostPort', $PostPort);
    $xml->addChild('PostURL', $PostURL);
    $xml->addChild('AuthToken', $AuthToken);
    $xml->addChild('PostRate', $PostRate);
    $xml->addChild('HighPrec', $HighPrec);

    print $xml->asXML();
  }


  /*
  Takes:
    • ID: the unique hardware id of the MTU
    • TYPE: the configuration type of the MTU
    • Timestamp: the "Unix Epoch" based timestamp of the reading.
    • Watts: the cumulative reading in watt hours by the MTU (since its creation) at the specified
      timestamp. Fractional Watt-Hours are represented as a decimal value up to 4 digits of
      precision.
    • Rate: The “rate in effect” when the reading was made (based on GW Utility configuration).

  Returns:
    HTTP 200 code if successful. Any code >= 300 will be considered to be an error code.

  Example input:
    <ted5000 GWID="200000" auth="MySecurityToken">
      <MTU ID=100000 type="0">
        <cumulative timestamp="123456000" watts="10000" rate="0.12345"/>
        <cumulative timestamp="123456060" watts="10005" rate="0.12345"/>
        <cumulative timestamp="123456120" watts="10010" rate="0.12345"/>
      </MTU>
      <MTU ID=100001 type="0">
        <cumulative timestamp="123456000" watts="10000" rate="0.12345"/>
        <cumulative timestamp="123456060" watts="10005" rate="0.12345"/>
        <cumulative timestamp="123456120" watts="10010" rate="0.12345"/>
      </MTU>
    </ted5000>
  */
  public function data()
  {
    $outp = fopen("~/ted-data/xml/xmlfile." . date("YmdHis") . ".xml", "w");
    fwrite($outp, $HTTP_RAW_POST_DATA);
    fclose($outp);
  }
}

/* End of file ted.php */
/* Location: ./application/controllers/ted.php */