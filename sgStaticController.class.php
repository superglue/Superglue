<?php
class sgStaticController extends sgBaseController {
  public function GET() {
    $paths = explode('/', sgContext::getCurrentPath());
    $this->title = ucwords(end($paths));
    return $this->render(substr(sgContext::getCurrentPath(), 1));
  }
}