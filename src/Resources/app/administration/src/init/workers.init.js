(function () {
  const factory = Shopware.Application.getContainer('factory');
  const workerNotificationFactory = factory.workerNotification;

  registerHashMiddleware(workerNotificationFactory);
})();

function registerHashMiddleware(factory) {
  const ids = {};
  const name = 'Eyecook\\Blurhash\\Message\\GenerateHashMessage';
  const fn = function (next, { entry, $root, notification }) {

    const dispatchReload = entry.size === 0 && ids.hasOwnProperty('blurhashes');

    messageQueueNotification('blurhashes', ids, next, entry, $root, notification, {
      title: 'ecBlurhash.notification-center.worker-listener.hashGeneration.title',
      message: 'ecBlurhash.notification-center.worker-listener.hashGeneration.message',
      success: 'ecBlurhash.notification-center.worker-listener.hashGeneration.success',
      foregroundSuccessMessage: 'ecBlurhash.notification-center.worker-listener.hashGeneration.success',
    });

    if (dispatchReload) {
      window.dispatchEvent(new Event('eyecook_blurhash_generated'));
    }
  };
  factory.register('GenerateHashMessage', { name, fn });

  // It seems this is the only way to have our message middleware called
  //  Possible solution would be to reset the middleware helper by calling `factory.resetHelper()`
  //  and then initialize it again by calling `factory.initialize();`. But this does not seem to work properly
  // Even better would be when the `factory.register` method do that for a single service when it is already initialized
  const helper = factory.initialize();
  helper.use(middlewareFunctionWrapper(name, fn));
  // The message-queue-stats may be polled here to have any existing messages displayed right away
}

/**
 * Helper function cloned from @shopware/administration
 * Abandon when respective source method is either exposed by shopware
 *  or implement in the use method of the MiddlewareHelper
 *
 * @see src/core/factory/worker-notification.factory.js
 * @internal
 */
function middlewareFunctionWrapper(name, fn) {
  return (next, data) => {
    const entry = data.queue.find(
      (q) => q.name === name
    ) || null;
    const mergedData = { ...data, ...{ entry, name } };

    if (entry === null) {
      next();
    } else {
      fn.call(null, next, mergedData);
    }
  };
}

/**
 * Helper function cloned from @shopware/administration
 * Abandon when respective source method is exposed by shopware
 *
 * @see src/core/factory/worker-notification.factory.js
 * @internal
 */
function messageQueueNotification(key, ids, next, entry, $root, notification, messages, multiplier = 1) {
  let notificationId = null;
  let didSendForegroundMessage = false;

  if (ids.hasOwnProperty((key))) {
    notificationId = ids[key].notificationId;
    didSendForegroundMessage = ids[key].didSendForegroundMessage;
  }

  if (entry.size) {
    entry.size *= multiplier;
  }

  const config = {
    title: $root.$tc(messages.title),
    message: $root.$tc(messages.message, entry.size),
    variant: 'info',
    metadata: {
      size: entry.size
    },
    growl: false,
    isLoading: true
  };

  // Create new notification
  if (entry.size && notificationId === null) {
    notification.create(config).then((uuid) => {
      notificationId = uuid;

      ids[key] = {
        notificationId,
        didSendForegroundMessage: false
      };
    });
    next();
  }

  // Update existing notification
  if (notificationId !== null) {
    config.uuid = notificationId;

    if (entry.size === 0) {
      config.title = $root.$tc(messages.title);
      config.message = $root.$t(messages.success);
      config.isLoading = false;

      if (messages.foregroundSuccessMessage && !didSendForegroundMessage) {
        const foreground = Object.assign({}, config);
        foreground.message = $root.$t(messages.foregroundSuccessMessage);
        delete foreground.uuid;
        delete foreground.isLoading;
        foreground.growl = true;
        foreground.variant = 'success';
        notification.create(foreground);

        ids[key] = {
          notificationId,
          didSendForegroundMessage: true
        };
      }

      delete ids[key];
    }
    notification.update(config);
  }
  next();
}
