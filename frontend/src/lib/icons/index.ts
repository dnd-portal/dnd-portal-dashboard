import bookSvg from './book.svg?raw';
import calendarSvg from './calendar.svg?raw';
import checklistSvg from './checklist.svg?raw';
import compassSvg from './compass.svg?raw';
import lockSvg from './lock.svg?raw';
import settingsSvg from './settings.svg?raw';
import usersSvg from './users.svg?raw';
import walletSvg from './wallet.svg?raw';

export const icons = {
    book: bookSvg,
    calendar: calendarSvg,
    checklist: checklistSvg,
    compass: compassSvg,
    lock: lockSvg,
    settings: settingsSvg,
    users: usersSvg,
    wallet: walletSvg
} as const;

export type IconName = keyof typeof icons;
