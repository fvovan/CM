<?php

class CM_Janus_RpcEndpoints {

    /**
     * @param string $serverKey
     * @param string $streamChannelKey
     * @param string $streamKey
     * @param int    $start
     * @param string $data
     * @return array
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_AuthRequired
     * @throws CM_Exception_Invalid
     * @throws CM_Exception_NotAllowed
     * @throws Exception
     */
    public static function rpc_publish($serverKey, $streamChannelKey, $streamKey, $start, $data) {
        $serverKey = (string) $serverKey;
        $streamChannelKey = (string) $streamChannelKey;
        $streamKey = (string) $streamKey;

        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $params = CM_Params::factory(CM_Params::jsonDecode($data), true);
        $server = $janus->getConfiguration()->findServerByKey($serverKey);

        if (!$server) {
            throw new CM_Exception_Invalid('Server `' . $serverKey . '` not found');
        }

        $streamChannelType = $params->getInt('streamChannelType');
        $session = new CM_Session($params->getString('sessionId'));
        $user = $session->getUser(true);

        $streamRepository = $janus->getStreamRepository();
        $streamChannel = $streamRepository->createStreamChannel($streamChannelKey, $streamChannelType, $server->getId(), 0);
        try {
            $streamRepository->createStreamPublish($streamChannel, $user, $streamKey, $start);
        } catch (CM_Exception_NotAllowed $ex) {
            $streamChannel->delete();
            throw $ex;
        }
        return ['streamChannelId' => $streamChannel->getId()];
    }

    /**
     * @param string $serverKey
     * @param string $streamChannelKey
     * @param string $streamKey
     * @param string $start
     * @param string $data
     * @return bool
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_AuthRequired
     * @throws CM_Exception_Invalid
     * @throws CM_Exception_Nonexistent
     * @throws CM_Exception_NotAllowed
     */
    public static function rpc_subscribe($serverKey, $streamChannelKey, $streamKey, $start, $data) {
        $serverKey = (string) $serverKey;
        $streamChannelKey = (string) $streamChannelKey;
        $streamKey = (string) $streamKey;

        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $params = CM_Params::factory(CM_Params::jsonDecode($data), true);
        $session = new CM_Session($params->getString('sessionId'));
        $user = $session->getUser(true);

        $streamRepository = $janus->getStreamRepository();
        $streamChannel = $streamRepository->findStreamChannelByKey($streamChannelKey);
        if (!$streamChannel) {
            throw new CM_Exception_Nonexistent("Stream channel `{$streamChannelKey}` does not exists");
        }
        try {
            $streamRepository->createStreamSubscribe($streamChannel, $user, $streamKey, $start);
        } catch (CM_Exception_NotAllowed $exception) {
            throw new CM_Exception_NotAllowed('Cannot subscribe: ' . $exception->getMessage());
        }
        return true;
    }

    /**
     * @param string $serverKey
     * @param string $streamChannelKey
     * @param string $streamKey
     * @return bool
     * @throws CM_Exception_AuthFailed
     * @throws CM_Exception_Invalid
     */
    public static function rpc_removeStream($serverKey, $streamChannelKey, $streamKey) {
        $serverKey = (string) $serverKey;
        $streamChannelKey = (string) $streamChannelKey;
        $streamKey = (string) $streamKey;

        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $streamRepository = $janus->getStreamRepository();
        $streamChannel = $streamRepository->findStreamChannelByKey($streamChannelKey);

        $streamSubscribe = $streamChannel->getStreamSubscribes()->findKey($streamKey);
        if ($streamSubscribe) {
            $streamRepository->removeStream($streamSubscribe);
        }
        $streamPublish = $streamChannel->getStreamPublishs()->findKey($streamKey);
        if ($streamPublish) {
            $streamRepository->removeStream($streamSubscribe);
        }
        return true;
    }

    /**
     * @param string $serverKey
     * @param string $sessionId
     * @param string $streamChannelKey
     * @return bool
     */
    public static function rpc_canUserSubscribe($serverKey, $sessionId, $streamChannelKey) {
        $serverKey = (string) $serverKey;
        $sessionId = (string) $sessionId;
        $streamChannelKey = (string) $streamChannelKey;

        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $session = new CM_Session($sessionId);
        $user = $session->getUser(true);

        $streamRepository = $janus->getStreamRepository();
        $streamChannel = $streamRepository->findStreamChannelByKey($streamChannelKey);

        $canSubscribeUntil = $streamChannel->canSubscribe($user, time());
        return $canSubscribeUntil < time();
    }

    /**
     * @param string $serverKey
     * @param string $sessionId
     * @return bool
     */
    public static function rpc_isValidUser($serverKey, $sessionId) {
        $serverKey = (string) $serverKey;
        $sessionId = (string) $sessionId;

        $janus = CM_Service_Manager::getInstance()->getJanus('janus');
        self::_authenticate($janus, $serverKey);

        $session = new CM_Session($sessionId);
        return $session->hasUser();
    }

    /**
     * @param CM_Janus_Service $janus
     * @param string           $serverKey
     * @throws CM_Exception_AuthFailed
     */
    protected static function _authenticate(CM_Janus_Service $janus, $serverKey) {
        if (!$janus->getConfiguration()->findServerByKey($serverKey)) {
            throw new CM_Exception_AuthFailed('Invalid serverKey');
        }
    }
}
