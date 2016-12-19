<?php
namespace Tieba;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class Tieba
{
    private $forumData;

    public function fetchForumPage($name)
    {
        try {
            $this->forumData = array(
                'kw' => $name,
                'pn' => '1',
                'q_type' => '2',
                'rn' => '100',
                'scr_dip' => '1.5',
                'scr_h' => '800',
                'scr_w' => '480',
                'st_type' => 'tb_forumlist',
                'with_group' => '1'
            );
            $result_raw = $this->fetch('http://c.tieba.baidu.com/c/f/frs/page');
            $result = json_decode($result_raw, true);
            if (count($result['thread_list']) === 0) {
                throw new TiebaException('network error');
            }
            return $result['thread_list'];
        } catch (TiebaException $exception) {
            echo $exception->getMessage();
        }
    }

    private function fetch($url)
    {
        try {
            $common_data = array(
                'from' => 'baidu_appstore',
                'stErrorNums' => '0',
                'stMethod' => '1',
                'stMode' => '1',
                'stSize' => mt_rand(50, 2000),
                'stTime' => mt_rand(50, 500),
                'stTimesNum' => '1',
                'timestamp' => time() . self::random(3, TRUE)
            );
            $pre_data = self::getClient() + $this->forumData + $common_data;
            ksort($pre_data);
            $forum_data = [];
            $forum_data += $pre_data;
            $sign_str = '';
            foreach ($forum_data as $key => $value) {
                $sign_str .= $key . '=' . $value;
            }
            $sign = strtoupper(md5($sign_str . 'tiebaclient!!!'));
            $forum_data['sign'] = $sign;

            $client = new Client(['timeout' => 10]);
            $response = $client->request('POST', $url, [
                'headers' => [
                    'User-Agent' => 'BaiduTieba for Android 6.0.1'
                ],
                'form_params' => $forum_data
            ]);
            return $response->getBody()->getContents();
        } catch (TransferException $exception) {
            throw new TiebaException('network error:' . $exception->getMessage());
        }
    }

    public static function random($length, $numeric = FALSE)
    {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    public static function getClient($type = NULL, $model = NULL, $version = NULL)
    {
        $client = array(
            '_client_id' => 'wappc_138' . self::random(10, TRUE) . '_' . self::random(3, TRUE),
            '_client_type' => null === $type ? mt_rand(1, 4) : $type,
            '_client_version' => null === $version ? '6.0.1' : $version,
            '_phone_imei' => md5(self::random(16, TRUE)),
            'cuid' => strtoupper(md5(self::random(16))) . '|' . self::random(15, TRUE),
            'model' => null === $model ? 'M1' : $model
        );
        return $client;
    }
}