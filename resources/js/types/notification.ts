export type NotificationKind =
    | 'payment'
    | 'order'
    | 'sale'
    | 'application'
    | 'withdrawal'
    | 'discussion'
    | 'info';

export type NotificationTone = 'success' | 'warning' | 'info';

/** One entry under the bell. Opening it marks it read and goes to its page. */
export type AppNotification = {
    id: string;
    kind: NotificationKind;
    tone: NotificationTone;
    title: string;
    body: string;
    read: boolean;
    created_at: string | null;
};
