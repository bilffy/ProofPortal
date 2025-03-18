export const encryptData = (rawData: string): string => {
    return btoa(btoa(rawData));
}

export const decryptData = (encryptedData: string): string => {
    return atob(atob(encryptedData));
}

export const encryptObjectValues = (obj: Record<string, string>): Record<string, string> => {
    const encryptedObj: Record<string, string> = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            encryptedObj[key] = encryptData(obj[key]);
        }
    }
    return encryptedObj;
}
