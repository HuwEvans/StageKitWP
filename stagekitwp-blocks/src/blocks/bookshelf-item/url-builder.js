export function buildAffiliateUrl({
    rawUrl,
    asin,
    domain,
    affiliateTag
}) {

    if (rawUrl) {
        return rawUrl;
    }

    if (!asin) {
        return '#';
    }

    let url =
        `https://${domain}/dp/${asin}`;

    if (affiliateTag) {
        url +=
            `?tag=${encodeURIComponent(
                affiliateTag
            )}`;
    }

    return url;
}