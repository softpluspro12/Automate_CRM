
{{#unless isLoading}}
<div class="list-nested-categories">

    <div class="clearfix">
        <div class="btn-group pull-right">
            <a href="javascript:" class="small dropdown-toggle btn-link" data-toggle="dropdown">
                <span class="fas fa-ellipsis-h"></span>
            </a>

            <ul class="dropdown-menu">
                {{#if hasExpandedToggler}}
                <li class="{{#if isExpanded}}hidden{{/if}}">
                    <a
                        href="javascript:"
                        class="category-expanded-toggle-link action"
                        data-action="expand"
                    >{{translate 'Expand'}}</a>
                </li>
                {{/if}}

                {{#if showEditLink}}
                <li>
                    <a
                        href="#{{scope}}"
                        class="action manage-categories-link"
                        data-action="manageCategories"
                    >{{translate 'Manage Categories' scope=scope}}</a>
                </li>
                {{/if}}
            </ul>
        </div>
        {{#if currentId}}
        <div class="category-item">
            <a
                href="javascript: "
                class="action folder-icon"
                data-action="openCategory"
                data-id="{{categoryData.upperId}}"
                title="{{translate 'Up'}}"
            ><span class="fas fa-arrow-up text-muted transform-flip-x"></span></a>
        </div>
        {{/if}}
    </div>

    <div class="grid-auto-fill-xs">
        {{#each list}}
        <div class="category-cell">
            <div class="category-item" data-id="{{id}}">
                <a
                    href="#{{../scope}}/view/{{id}}"
                    class="action folder-icon"
                    data-action="openCategory"
                    data-id="{{id}}"
                    data-name="{{name}}"
                ><span class="far fa-folder text-muted"></span></a>
                <a
                    href="#{{../scope}}/view/{{id}}"
                    class="action link-gray"
                    data-action="openCategory"
                    data-id="{{id}}"
                    data-name={{name}}
                    title="{{name}}"
                >{{name}}</a>
            </div>
        </div>
        {{/each}}

        {{#if showMoreIsActive}}
        <div class="category-cell">
            <div class="category-item show-more">
                <span class="category-item-name">
                    <a
                        href="javascript:"
                        class="action"
                        data-action="showMore"
                        title="{{translate 'Show more'}}"
                    >...</a>
                </span>
            </div>
        </div>
        {{/if}}
    </div>
</div>
{{else}}
<div class="list-nested-categories" style="height: 100px;">

</div>
{{/unless}}