import { initDragFunction } from './modules/drag.js';
import { initDropdownFunction } from './modules/dropdown.js';
import { tableEditor } from './modules/tableEditor.js';
import { filterInit } from './modules/filter.js';
import { initGallery } from './modules/gallery.js';
import { initPagination } from './modules/pagination.js';
import { statusFunction } from './modules/status.js';
import { initTableFunction } from './modules/table.js';
import { mobileMenuFunction } from './modules/mobile-menu.js';
import { headerFunction } from './modules/header.js';
import * as webpFunction from './modules/webp.js';

/* Webp checking */
webpFunction.isWebp();

headerFunction();
mobileMenuFunction();
initGallery();
initDropdownFunction();
initPagination();
initTableFunction();
filterInit();
initDragFunction();
tableEditor();
statusFunction();
