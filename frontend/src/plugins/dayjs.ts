import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import localizedFormat from 'dayjs/plugin/localizedFormat'

export function setupDayjs() {
  dayjs.extend(relativeTime)
  dayjs.extend(utc)
  dayjs.extend(timezone)
  dayjs.extend(localizedFormat)
}
