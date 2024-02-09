export const sortArray = (array, index, newIndex) => {
  const newArray = array.slice();
  [newArray[index], newArray[newIndex]] = [newArray[newIndex], newArray[index]];
  return newArray;
};

export const addClass = (elem, ...className) => {
  if (elem) {
    elem.classList.add(...className);
  }
};

export const removeClass = (elem, ...className) => {
  if (elem) {
    elem.classList.remove(...className);
  }
};

export const hasClass = (elem, className) => {
  if (elem) {
    return elem.classList.contains(className);
  }
  return false;
};


export const getTableColumn = (table, cellIndex) =>
  table.querySelectorAll(
    `td:nth-child(${cellIndex + 1}),th:nth-child(${cellIndex + 1})`,
  );

export const getStyleVal = (elm, css) => {
  const styles = window.getComputedStyle(elm, null);
  return styles.getPropertyValue(css);
};

export const parseString = (str) => parseInt(str.match(/\d+/));
