import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';

export interface PortalNotification {
    id: string;
    type: string;
    read_at: string | null;
    created_at: string;
    data: Record<string, any>;
}

export const iconForNotification = (type: string): string => {
    switch (type) {
        case 'bill_published':
            return 'scroll-text';
        case 'participation_opened':
            return 'calendar-check';
        case 'submission_aggregated':
            return 'check-circle-2';
        case 'legislator_follow_up':
            return 'message-square';
        default:
            return 'bell';
    }
};

export const titleForNotification = (notification: PortalNotification): string => {
    switch (notification.type) {
        case 'bill_published':
            return `New bill published: ${notification.data.title}`;
        case 'participation_opened':
            return `Commentary open: ${notification.data.title}`;
        case 'submission_aggregated':
            return 'Your submission is now in the committee report';
        case 'legislator_follow_up':
            return `${notification.data.sender?.name ?? 'Legislator'} requested a follow-up`;
        default:
            return 'Portal notification';
    }
};

export const bodyForNotification = (notification: PortalNotification): string => {
    switch (notification.type) {
        case 'bill_published':
            return 'A fresh bill has been filed. Review the brief and get ready to weigh in.';
        case 'participation_opened':
            return 'The public commentary window is now open. Share your input before the deadline.';
        case 'submission_aggregated':
            return 'Your views have been captured in the committee report. We will notify you of the deliberations.';
        case 'legislator_follow_up':
            return notification.data.subject ?? 'A legislator would like to discuss your submission.';
        default:
            return 'You have a new update in the participation portal.';
    }
};

export const linkForNotification = (notification: PortalNotification): string | null => {
    switch (notification.type) {
        case 'bill_published':
        case 'participation_opened':
            return notification.data.bill_id ? billRoutes.show({ bill: notification.data.bill_id }).url : null;
        case 'submission_aggregated':
        case 'legislator_follow_up':
            return notification.data.submission_id ? submissionRoutes.show({ submission: notification.data.submission_id }).url : null;
        default:
            return null;
    }
};

export const formatNotificationDate = (value: string): string => {
    return new Date(value).toLocaleString();
};

export const relativeTimeFromNow = (value: string): string => {
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const now = Date.now();
    const then = new Date(value).getTime();
    const diffMs = then - now;

    const minutes = Math.round(diffMs / (60 * 1000));

    if (Math.abs(minutes) < 60) {
        return formatter.format(minutes, 'minute');
    }

    const hours = Math.round(minutes / 60);

    if (Math.abs(hours) < 24) {
        return formatter.format(hours, 'hour');
    }

    const days = Math.round(hours / 24);

    if (Math.abs(days) < 7) {
        return formatter.format(days, 'day');
    }

    const weeks = Math.round(days / 7);

    if (Math.abs(weeks) < 5) {
        return formatter.format(weeks, 'week');
    }

    const months = Math.round(days / 30);

    if (Math.abs(months) < 12) {
        return formatter.format(months, 'month');
    }

    const years = Math.round(days / 365);

    return formatter.format(years, 'year');
};
