/**
 * Unit tests for notification dropdown + toast deduplication
 * Covers "reached" notification scenarios: bell click, usage_limit_reached, duplicates
 */

// Simulate the fixed handleNewNotification logic
function createDropdownMock() {
  return {
    notifications: [],
    unreadCount: 0,
    handleNewNotification(notification) {
      const apptId = notification.data?.appointment_id || notification.data?.data?.appointment_id || notification.data?.id;
      const notifType = notification.data?.type || notification.type;
      const title = notification.data?.title || notification.title || '';
      const message = notification.data?.message || notification.message || notification.data?.body || '';
      const dedupKey = apptId ? `${notifType}-${apptId}` : `${notifType}-${title}|${message}`;

      const existingForKey = this.notifications.find(n => {
        const nApptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
        const nType = n.data?.type || n.type;
        const nTitle = n.data?.title || n.title || '';
        const nMessage = n.data?.message || n.message || n.data?.body || '';
        const nKey = nApptId ? `${nType}-${nApptId}` : `${nType}-${nTitle}|${nMessage}`;
        return nKey === dedupKey;
      });

      if (existingForKey) {
        const index = this.notifications.indexOf(existingForKey);
        this.notifications[index] = {
          ...existingForKey,
          id: notification.id,
          type: notification.type,
          data: notification.data,
          created_at: new Date().toISOString(),
          title: notification.title || notification.data?.title || 'Notification',
          message: notification.message || notification.data?.message || notification.body
        };
        this.unreadCount = this.notifications.filter(n => !n.read_at).length;
        return 'updated';
      }

      this.notifications.unshift({
        id: notification.id,
        type: notification.type,
        data: notification.data,
        read_at: null,
        created_at: new Date().toISOString(),
        title: notification.title || notification.data?.title || 'Notification',
        message: notification.message || notification.data?.message || notification.body
      });
      this.unreadCount = this.notifications.filter(n => !n.read_at).length;
      return 'added';
    },
    loadNotificationsMerge(apiNotifications) {
      const existingIds = new Set(this.notifications.map(n => n.id));
      const existingDedupKeys = new Set();
      this.notifications.forEach(n => {
        const apptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
        const nType = n.data?.type || n.type;
        const nTitle = n.data?.title || n.title || '';
        const nMessage = n.data?.message || n.message || n.data?.body || '';
        const key = apptId ? `${nType}-${apptId}` : `${nType}-${nTitle}|${nMessage}`;
        if (nType) existingDedupKeys.add(key);
      });
      const newApiNotifications = (apiNotifications || []).filter(n => {
        if (existingIds.has(n.id)) return false;
        const apptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
        const nType = n.data?.type || n.type;
        const nTitle = n.data?.title || n.title || '';
        const nMessage = n.data?.message || n.message || n.data?.body || '';
        const key = apptId ? `${nType}-${apptId}` : `${nType}-${nTitle}|${nMessage}`;
        if (existingDedupKeys.has(key)) return false;
        return true;
      });
      if (newApiNotifications.length > 0) {
        this.notifications = [...newApiNotifications, ...this.notifications];
      }
      this.unreadCount = this.notifications.filter(n => !n.read_at).length;
      return newApiNotifications.length;
    }
  };
}

// Toast deduplication simulation (from unified-notifications.js)
function createToastMock() {
  return {
    recentToasts: new Map(),
    toastDebounceMs: 1000,
    toasts: [],
    showToast({ title, message }) {
      const toastHash = `${title}|${message}`;
      const now = Date.now();
      const lastShown = this.recentToasts.get(toastHash);
      if (lastShown && (now - lastShown) < this.toastDebounceMs) {
        return 'debounced';
      }
      this.recentToasts.set(toastHash, now);
      // DOM check
      for (let t of this.toasts) {
        if (t.title === title && t.message === message) return 'dom-duplicate';
      }
      this.toasts.push({ title, message });
      return 'shown';
    }
  };
}

// Minimal test runner
function assert(cond, msg) {
  if (!cond) throw new Error(msg);
}
let passed = 0, failed = 0;
function test(name, fn) {
  try { fn(); console.log(`✓ ${name}`); passed++; } catch (e) { console.error(`✗ ${name}: ${e.message}`); failed++; }
}

// --- Tests ---

test('reached appointment notification appears in dropdown on first push', () => {
  const dd = createDropdownMock();
  const res = dd.handleNewNotification({
    id: 'appointment_booked-42-123',
    type: 'appointment_booked',
    data: { type: 'appointment_booked', appointment_id: 42, title: 'New Appointment Booked', message: 'Patient has booked' },
    title: 'New Appointment Booked',
    message: 'Patient has booked'
  });
  assert(res === 'added', 'should add');
  assert(dd.notifications.length === 1, 'length 1');
  assert(dd.unreadCount === 1, 'unread 1');
});

test('duplicate appointment notification updates instead of adding (toast dedup)', () => {
  const dd = createDropdownMock();
  dd.handleNewNotification({
    id: 'appointment_booked-42-1', type: 'appointment_booked',
    data: { type: 'appointment_booked', appointment_id: 42, title: 'New Appointment Booked', message: 'Patient has booked' }
  });
  const res = dd.handleNewNotification({
    id: 'appointment_booked-42-2', type: 'appointment_booked',
    data: { type: 'appointment_booked', appointment_id: 42, title: 'New Appointment Booked', message: 'Patient has booked' }
  });
  assert(res === 'updated', 'should update not add');
  assert(dd.notifications.length === 1, 'still 1');
});

test('different type for same appointment should add separately', () => {
  const dd = createDropdownMock();
  dd.handleNewNotification({ id: 'booked-42', type: 'appointment_booked', data: { type: 'appointment_booked', appointment_id: 42, title: 'Booked', message: 'msg' } });
  dd.handleNewNotification({ id: 'cancelled-42', type: 'appointment_cancelled', data: { type: 'appointment_cancelled', appointment_id: 42, title: 'Cancelled', message: 'msg' } });
  assert(dd.notifications.length === 2, 'should have 2 different types');
});

test('usage_limit_reached reached notification shows correctly and dedupes via title|message', () => {
  const dd = createDropdownMock();
  const notif = {
    id: 'usage_limit_reached-no-id-123',
    type: 'usage_limit_reached',
    data: { type: 'usage_limit_reached', title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' },
    title: 'Usage Limit Reached',
    message: 'You have reached your monthly usage limit.'
  };
  assert(dd.handleNewNotification(notif) === 'added', 'first add');
  // duplicate within debounce should update not duplicate
  assert(dd.handleNewNotification({ ...notif, id: 'usage_limit_reached-no-id-456' }) === 'updated', 'second should update');
  assert(dd.notifications.length === 1, 'still 1 after duplicate');
});

test('bell click merge does not duplicate usage_limit_reached after API fetch', () => {
  const dd = createDropdownMock();
  // Realtime pushed first (as toast would)
  dd.handleNewNotification({
    id: 'usage_limit_reached-no-id-123',
    type: 'usage_limit_reached',
    data: { type: 'usage_limit_reached', title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' },
    title: 'Usage Limit Reached',
    message: 'You have reached your monthly usage limit.'
  });
  // Simulate API response after bell click (UUID from DB)
  const apiNotifications = [{
    id: 'uuid-from-db-1',
    type: 'App\\Notifications\\TestNotification',
    data: { type: 'usage_limit_reached', title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' },
    title: 'Usage Limit Reached',
    message: 'You have reached your monthly usage limit.',
    read_at: null,
    created_at: new Date().toISOString()
  }];
  const added = dd.loadNotificationsMerge(apiNotifications);
  assert(added === 0, `should add 0 after dedup, got ${added}`);
  assert(dd.notifications.length === 1, `should still be 1, got ${dd.notifications.length}`);
});

test('bell click merge correctly adds new API notifications that are not duplicates', () => {
  const dd = createDropdownMock();
  dd.handleNewNotification({
    id: 'booked-42', type: 'appointment_booked',
    data: { type: 'appointment_booked', appointment_id: 42, title: 'Booked 42', message: 'msg' }
  });
  const apiNotifications = [
    { id: 'uuid-99', data: { type: 'appointment_booked', appointment_id: 99, title: 'Booked 99', message: 'msg99' }, title: 'Booked 99', message: 'msg99', read_at: null },
    { id: 'uuid-42-dup', data: { type: 'appointment_booked', appointment_id: 42, title: 'Booked 42', message: 'msg' }, title: 'Booked 42', message: 'msg', read_at: null }
  ];
  const added = dd.loadNotificationsMerge(apiNotifications);
  assert(added === 1, `should add only 1 new (99), got ${added}`);
  assert(dd.notifications.length === 2, 'should have 2 total');
});

test('toast shows once and debounces duplicates within 1s', () => {
  const tm = createToastMock();
  assert(tm.showToast({ title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' }) === 'shown', 'first shown');
  assert(tm.showToast({ title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' }) === 'debounced', 'second debounced');
  assert(tm.toasts.length === 1, 'only 1 toast');
});

test('toast allows same title with different message', () => {
  const tm = createToastMock();
  tm.showToast({ title: 'Usage Limit Reached', message: 'You have reached your monthly usage limit.' });
  const res = tm.showToast({ title: 'Usage Limit Reached', message: 'You are approaching your limit.' });
  assert(res === 'shown', 'different message should show');
  assert(tm.toasts.length === 2, '2 toasts');
});

test('dropdown handles multiple types correctly including usage + appointment', () => {
  const dd = createDropdownMock();
  dd.handleNewNotification({ id: 'u1', type: 'usage_limit_reached', data: { type: 'usage_limit_reached', title: 'Usage Limit Reached', message: 'limit msg' } });
  dd.handleNewNotification({ id: 'a1', type: 'appointment_booked', data: { type: 'appointment_booked', appointment_id: 1, title: 'Booked', message: 'msg' } });
  dd.handleNewNotification({ id: 's1', type: 'system_alert', data: { type: 'system_alert', title: 'Alert', message: 'alert msg' } });
  assert(dd.notifications.length === 3, '3 distinct types');
  assert(dd.unreadCount === 3, 'unread 3');
});

console.log(`\n${passed} passed, ${failed} failed`);
if (failed > 0) process.exit(1);
