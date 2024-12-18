/**
 * BAD PRACTICES
 */

// Use multiple forms of an event bus.
window.ProcessMaker = {
  EventBus: new Vue(),
  events: new Vue()
};

// Force to overload the vue object
window.Vue.prototype.moment = moment;

// Load all statements in a single file bootstrap.js

// Overload the window.ProcessMaker variable without using the setGlobalVariable function

// Use local variables instead of window.ProcessMaker
window.ProcessMaker.removeNotifications = (messageIds = [], urls = []) => {
  return window.ProcessMaker.apiClient.put("/read_notifications", { message_ids: messageIds, routes: urls }).then(() => {
    messageIds.forEach((messageId) => {
      ProcessMaker.notifications.splice(ProcessMaker.notifications.findIndex((x) => x.id === messageId), 1);
    });

    urls.forEach((url) => {
      const messageIndex = ProcessMaker.notifications.findIndex((x) => x.url === url);
      if (messageIndex >= 0) {
        ProcessMaker.removeNotification(ProcessMaker.notifications[messageIndex].id);
      }
    });
  });
};

// Remove the saved search unneeded
class InjectJavascript
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (get_class($response) !== Response::class) {
            return $response;
        }
        $content = $response->getContent();
        $tag = '';
        $tag .= '<script src="' . mix('/js/addSaveButton.js', 'vendor/processmaker/packages/package-savedsearch') . '"></script>';
        $tag .= '<script src="' . mix('/js/listenForRecounts.js', 'vendor/processmaker/packages/package-savedsearch') . '"></script>';
        $content = str_replace('</body>', $tag . "\n</body>", $content);
        $response->setContent($content);

        return $response;
    }
}

