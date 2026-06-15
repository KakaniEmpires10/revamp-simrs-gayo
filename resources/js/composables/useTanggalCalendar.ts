import { parseDate } from '@internationalized/date';
import type { DateValue } from 'reka-ui';
import { computed, reactive, ref  } from 'vue';
import type {Ref} from 'vue';
import { formatTanggalIndonesia } from '@/lib/tanggal';

type TanggalModel = {
    value: string | undefined;
};

export function useTanggalCalendar(model: TanggalModel | Ref<string | undefined>, placeholder = 'Pilih tanggal') {
    const open = ref(false);

    const value = computed<DateValue | undefined>({
        get: () => {
            if (!model.value) {
                return undefined;
            }

            try {
                return parseDate(model.value);
            } catch {
                return undefined;
            }
        },
        set: (dateValue) => {
            model.value = dateValue?.toString() ?? '';
            open.value = false;
        },
    });

    const label = computed(() => model.value ? formatTanggalIndonesia(model.value) : placeholder);

    return reactive({
        open,
        value,
        label,
    });
}
