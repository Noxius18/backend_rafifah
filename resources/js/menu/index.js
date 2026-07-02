import { registerJadwalTesIndex } from './jadwal-tes-index';
import { registerMahasantriShow } from './mahasantri-show';
import { registerJadwalTesNilai } from './jadwal-tes-nilai';

export function registerMenuPages(Alpine) {
    registerJadwalTesIndex(Alpine);
    registerMahasantriShow(Alpine);
    registerJadwalTesNilai(Alpine);
}
