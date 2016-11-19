<?php

/**
 * Created by PhpStorm.
 * User: songlian
 * Date: 18/11/2016
 * Time: 18:29
 */
final class PhabricatorMailImplementationAliyunAdapter extends PhabricatorMailImplementationAdapter
{

  private $client;
  private $request;

  /**
   * PhabricatorMailImplementationAliyunAdapter constructor.
   */
  public function __construct()
  {
    $root = phutil_get_library_root('phabricator');
    $root = dirname($root);
    require_once $root . '/externals/aliyun/aliyun-php-sdk-core/Config.php';
    require_once $root . '/externals/aliyun/aliyun-php-sdk-dm/Dm/Request/V20151123/SingleSendMailRequest.php';

    $region = PhabricatorEnv::getEnvConfig('aliyun.region');
    $access_key = PhabricatorEnv::getEnvConfig('aliyun.accessKey');
    $access_secret = PhabricatorEnv::getEnvConfig('aliyun.accessSecret');

    $profile = DefaultProfile::getProfile($region, $access_key, $access_secret);
    $this->client = new DefaultAcsClient($profile);
    $this->request = new Dm\Request\V20151123\SingleSendMailRequest();

    $this->request->setAddressType(1);
    $this->request->setReplyToAddress("false");
  }

  /**
   * @param $email
   * @param string $name
   * @return $this
   */
  public function setFrom($email, $name = '')
  {
    $address = PhabricatorEnv::getEnvConfig("aliyun.fromAddress");
    $this->request->setAccountName($address);
    $this->request->setFromAlias($name);
    return $this;
  }

  /**
   * @param $email
   * @param string $name
   * @return $this
   */
  public function addReplyTo($email, $name = '')
  {
    // 阿里与不支持动态回信地址
    return $this;
  }

  /**
   * @param array $emails
   * @return $this
   */
  public function addTos(array $emails)
  {
    error_log(join(',', $emails));
    error_log(gettype($emails));

    $addresses = $this->request->getToAddress();
    if (!empty($addresses)) {
      $addresses = $addresses . ',';
    }
    $addresses = $addresses . join(',', $emails);
    $this->request->setToAddress($addresses);
    return $this;
  }

  /**
   * @param array $emails
   * @return $this
   */
  public function addCCs(array $emails)
  {
    // 阿里云不支持添加抄送,只能添加到发信人列表了
    return $this->addTos($emails);
  }

  /**
   * @param $data
   * @param $filename
   * @param $mimetype
   * @return $this
   */
  public function addAttachment($data, $filename, $mimetype)
  {
    // 阿里云不支持附件
    return $this;
  }

  /**
   * @param $header_name
   * @param $header_value
   * @return $this
   */
  public function addHeader($header_name, $header_value)
  {
    // 阿里云不支持
    return $this;
  }

  /**
   * @param $plaintext_body
   * @return $this
   */
  public function setBody($plaintext_body)
  {
    $this->request->setTextBody($plaintext_body);
    return $this;
  }

  /**
   * @param $html_body
   * @return $this
   */
  public function setHTMLBody($html_body)
  {
    $this->request->setHtmlBody($html_body);
    return $this;
  }

  /**
   * @param $subject
   * @return $this
   */
  public function setSubject($subject)
  {
    $this->request->setSubject($subject);
    return $this;
  }

  /**
   * @return boolean
   */
  public function supportsMessageIDHeader()
  {
    return false;
  }

  /**
   * @return boolean
   */
  public function send()
  {
    $send_result = true;
    try {
      $this->client->getAcsResponse($this->request);
    } catch (ClientException  $e) {
      $send_result = false;
    } catch (ServerException  $e) {
      $send_result = false;
    }
    return $send_result;
  }
}