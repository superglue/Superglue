<?php
class sgStaticController extends sgBaseController {
  public function GET() {
    return $this->render(substr(sgContext::getCurrentPath(), 1));
  }
}