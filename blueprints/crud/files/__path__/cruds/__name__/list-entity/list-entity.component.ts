import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from "@angular/router";

@Component({
    selector: 'app-list-entity',
    templateUrl: 'list-entity.component.html',
    styleUrls: ['list-entity.component.scss']
})
export class ListEntityComponent implements OnInit {

    entity:string;
    private static title: any;

        constructor(private route: ActivatedRoute) {
        this.route.params
            .subscribe(params =>{ console.log(params); ListEntityComponent.title = this.entity = params['entityName'] });
    }

    ngOnInit(): void {

    }

}
