<?php

class CM_Http_Response_Resource_Layout extends CM_Http_Response_Resource_Abstract {

    protected function _process() {
        $path = $this->getRequest()->getPath();
        $content = null;
        $mimeType = null;

        if ($pathRaw = $this->getRender()->getLayoutPath('resource/' . $path, null, null, true, false)) {
            $file = new CM_File($pathRaw);
            if (in_array($file->getExtension(), $this->_getFiletypesForbidden())) {
                throw new CM_Exception_Nonexistent('Forbidden filetype', ['path' => $path], ['severity' => CM_Exception::WARN]);
            }
            $content = $file->read();
            $mimeType = $file->getMimeType();
        } elseif ($pathTpl = $this->getRender()->getLayoutPath('resource/' . $path . '.smarty', null, null, true, false)) {
            $content = $this->getRender()->fetchTemplate($pathTpl);
            $mimeType = CM_File::getMimeTypeByContent($content);
        } else {
            throw new CM_Exception_Nonexistent('Invalid filename', ['path' => $path], ['severity' => CM_Exception::WARN]);
        }
        $this->setHeader('Content-Type', $mimeType);
        $this->_setContent($content);
    }

    /**
     * @return string[]
     */
    protected function _getFiletypesForbidden() {
        return ['smarty'];
    }

    public static function match(CM_Http_Request_Abstract $request) {
        return $request->getPathPart(0) === 'layout';
    }
}
