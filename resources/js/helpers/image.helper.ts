export const getAssetUrl = (fileLocationFromAssets: string) => {
  const url = new URL('/resources/assets/' + fileLocationFromAssets, import.meta.url);
  return url.href;
}
