export const isWebp = () => {
  const testWebP = (callback) => {
    const webP = new Image();
    webP.onload = webP.onerror = () => {
      callback(webP.height === 2);
    };
    webP.src =
      // eslint-disable-next-line max-len
      'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
  };
  testWebP((support) => {
    const html = document.documentElement;
    support ? html.classList.add('webp') : html.classList.add('no-webp');
  });
};
