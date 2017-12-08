<?php
/**
 * The MfindSalesAdapter REST API PHP Wrapper
 *
 * This class connects to the Mfind SalesAdapter REST API and performs actions on that API
 *
 * @author Piotr Radzikowski <piotr.radzikowski@mfind.pl>
 */

class MfindSalesAdapterAPI {
  public
    $last_response;
  protected
    $api_url = 'http://sales-adapter-stg.mfind.pl',
    $headers = ['Content-Type' => 'application/json'],
    $return_type,
    $handle,
    $api_version = 'v1';

  const
    METHOD_DELETE = 'DELETE',
    METHOD_GET    = 'GET',
    METHOD_POST   = 'POST',
    METHOD_PUT    = 'PUT',
    METHOD_PATCH  = 'PATCH';

  const
    LEADS_PATH = '/leads';

  function __construct($headers, $api_url, $version) {
    if ($version)
      $this->api_version = $version;
    if ($api_url)
      $this->api_url = $api_url . '/' . $this->api_version;
    if (!is_array($headers)) {
      throw new MfindSalesAdapterAPIException('First parameters in constructor should be array with headers');
    } else {
      $this->headers = array_merge($this->headers, $headers);
    }

    // If the cURL handle doesn't exist, create it
    if (is_null($this->handle)) {
      $this->handle = curl_init();
      $options = [
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 240,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_BUFFERSIZE => 128000
      ];
      curl_setopt_array($this->handle, $options);
    }
  }

  /**
   * Create a new lead
   *
   * @param array $data
   * @return mixed
   * @throws MfindSalesAdapterAPIException
   */
  public function create($data)
  {
    $data = array(
      'create_lead' => array_filter($data),
      'notes' => array(
        'source' => gethostname()
      )
    );

    return $this->request(self::LEADS_PATH, $data, self::METHOD_POST);
  }

  /**
   * Update an existing object
   *
   * @param string $object_id
   * @param array $data
   * @return mixed
   * @throws MfindSalesAdapterAPIException
   */
  public function update($object_id, $data)
  {
    return $this->request(self::LEADS_PATH . '/' . $object_id, $data, self::METHOD_PUT);
  }

  /**
   * Delete a record
   *
   * @param string $object_id
   * @return mixed
   * @throws MfindSalesAdapterAPIException
   */
  public function delete($object_id)
  {
    return $this->request(self::LEADS_PATH . '/' . $object_id, null, self::METHOD_DELETE);
  }

  /**
   * Makes a request to the API
   *
   * @param string $path The path to use for the API request
   * @param array $params
   * @param string $method
   * @return mixed
   * @throws MfindSalesAdapterAPIException
   */
  protected function request($path, $params = [], $method = self::METH_GET)
  {
    // Add any custom fields to the request
    if (!empty($params)) {
      $params = array_filter($params);
      if ($this->headers['Content-Type'] == 'application/json') {
        $json_params = json_encode($params);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $json_params);
      } else {
        $http_params = http_build_query($params);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $http_params);
      }
    }
    $url = $this->api_url . $path;

    // Modify the request depending on the type of request
    switch($method)
    {
      case 'POST':
        curl_setopt($this->handle, CURLOPT_POST, true);
        break;
      case 'GET':
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, []);
        curl_setopt($this->handle, CURLOPT_POST, false);
        if (!empty($params))
          $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        break;
      default:
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
        break;
    }

    $request_headers = [];
    foreach($this->headers as $key => $header) {
      $request_headers[] = $key . ': ' . $header;
    }

    curl_setopt($this->handle, CURLOPT_URL, $url);
    curl_setopt($this->handle, CURLOPT_HTTPHEADER, $request_headers);

    $response = curl_exec($this->handle);
    $response = $this->_checkForRequestErrors($response, $this->handle);
    $result = json_decode($response);

    return $result;
  }

  /**
   * Checks for errors in a request
   *
   * @param string $response The response from the server
   * @param Resource $handle The CURL handle
   * @return string The response from the API
   * @throws MfindSalesAdapterAPIException
   */
  private function _checkForRequestErrors($response, $handle) {
    $curl_error = curl_error($handle);
    if ($curl_error !== '') {
      throw new MfindSalesAdapterAPIException($curl_error);
    }
    $request_info = curl_getinfo($handle);

    switch($request_info['http_code']) {
      case 300:
      case 200:
      case 201:
      case 204:
        if ($response === '')
          return json_encode(['success' => true]);
        break;
      default:
        $result = json_decode($response);
        if (isset($result->create_lead)) {
          throw new MfindSalesAdapterAPIException($result->create_lead->message);
        } else {
          throw new MfindSalesAdapterAPIException($response);
        }
        break;
    }
    $this->last_response = $response;
    return $response;
  }
}

class MfindSalesAdapterAPIException extends Exception {}
