import { Component, OnInit } from '@angular/core';
import {BackendService} from "../backend.service";
import {ActivatedRoute} from "@angular/router";

@Component({
  selector: 'app-form-entity',
  templateUrl: './form-entity.component.html',
  styleUrls: ['./form-entity.component.scss']
})
export class FormEntityComponent implements OnInit {
  private static title: any;
  entity:string;
  formGroup;
  formMeta;

  constructor(private route: ActivatedRoute, private backend:BackendService) {
    this.route.params
        .subscribe(params =>{
          console.log(params);
          FormEntityComponent.title = this.entity = params['entityName']
          this.loadFormMeta();
        });


  }

  private loadFormMeta() {
    this.backend.form(this.entity).subscribe((a)=> this.formMeta = a)
  }

  ngOnInit() {
  }
}
