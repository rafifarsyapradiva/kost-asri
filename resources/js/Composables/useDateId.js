import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.locale('id');
dayjs.extend(relativeTime);

export function useDateId() {
    const formatDate = (date, format = 'DD MMMM YYYY') => {
        if (!date) return '-';
        return dayjs(date).format(format);
    };

    const formatDateTime = (date) => {
        if (!date) return '-';
        return dayjs(date).format('DD MMM YYYY, HH:mm [WIB]');
    };

    const fromNow = (date) => {
        if (!date) return '-';
        return dayjs(date).fromNow();
    };

    const formatCountdown = (targetDate) => {
        if (!targetDate) return '00:00:00';
        const now = dayjs();
        const target = dayjs(targetDate);
        const diffSeconds = target.diff(now, 'second');
        if (diffSeconds <= 0) return '00:00:00';

        const hours = Math.floor(diffSeconds / 3600);
        const minutes = Math.floor((diffSeconds % 3600) / 60);
        const seconds = diffSeconds % 60;

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    };

    return {
        dayjs,
        formatDate,
        formatDateTime,
        fromNow,
        formatCountdown,
    };
}

