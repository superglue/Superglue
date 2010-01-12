<?php
class sgMagicController extends sgBaseController {
  public function GET() {
    $paths = explode('/', sgContext::getCurrentPath());
    $this->title = ucwords(str_replace(array('_', '-'), ' ', end($paths)));
    return $this->render(substr(sgContext::getCurrentPath(), 1));
  }
}