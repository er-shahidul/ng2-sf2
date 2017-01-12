class ItemList<T> {
    items:T[] = [];
}
export class ListResponse<T> {
    page: number = 1;
    limit: number = 2;
    pages: number = 0;
    total: number = 0;
    _embedded: ItemList<T> = new ItemList<T>();

    constructor(page: number, limit: number, pages: number, total: number, embedded: ItemList<T>) {
        this.page = page;
        this.limit = limit;
        this.pages = pages;
        this.total = total;
        this._embedded = embedded;
    }

    get items(): T[] {
        return this._embedded.items;
    }
}