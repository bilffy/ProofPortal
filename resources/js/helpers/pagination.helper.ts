import type { LinkProps, PaginationLink } from "@/types/pagination.type";

export const PER_PAGE = 20;

export const buildLinkIndices = (links: never[] | PaginationLink[]) : Array<number | null> => {
    const activeIndex = links.findIndex((link: PaginationLink) => link.active);
    const lastIndex = links.length - 2; // length includes Prev and Next links
    const adjacentLeft = activeIndex - 2 <= 1 ? activeIndex - 1 : null;
    const adjacentRight = lastIndex === activeIndex
        ? activeIndex
        : activeIndex + 2 >= lastIndex ? activeIndex + 1 : null;    

    return [0, 1, adjacentLeft, activeIndex, adjacentRight, lastIndex, lastIndex + 1];
};

export const buildLinks = (links: never[] | PaginationLink[], indices: Array<number | null>) : Array<LinkProps> => {
    let finalIndices:(number|null)[] = [];
    
    indices.forEach(value => {
        if (null === value) {
            finalIndices.push(value);
        } else if (!finalIndices.includes(value)) {
            finalIndices.push(value);
        }
    });
    
    const finalLinks = finalIndices.map((index: number | null) => {
        if (null === index) {
            return {
                url: null,
                active: false,
                label: '...',
                isNext: false,
                isPrev: false,
                isEllipsis: true
            };
        }
        const link: PaginationLink | undefined = links.at(index);
        return {
            ...link,
            isPrev: link?.label.includes('Previous'),
            isNext: link?.label.includes('Next'),
            isEllipsis: false
        };
    });

    return finalLinks;
};
