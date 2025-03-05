export const encryptData = (rawData: string): string => {
    return btoa(btoa(rawData));
}

export const decryptData = (encryptedData: string): string => {
    return atob(atob(encryptedData));
}