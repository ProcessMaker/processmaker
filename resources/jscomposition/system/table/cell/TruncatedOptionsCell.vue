<template>
	<div class="tw-relative ">
		<div class="tw-text-nowrap tw-whitespace-nowrap tw-overflow-hidden tw-text-ellipsis"
			:style="{maxWidth: column.width + 'px'}">
			<a
				href="#"
				v-for="(item, index) in row[column.field]"
				:key="index"
				class="hover:tw-text-blue-400
					[&:not(:last-child)::after]:tw-content-['|'] after:tw-text-gray-400/70 after:tw-text-lg"
				@click.stop.self="onClick($event, item, index)"
					>
				{{ item.name }}
			</a>
		</div>
		
		<SimplePopover :show="show" @close="onClose">
			<ul class="tw-list-none tw-overflow-hidden tw-rounded tw-w-50 tw-text-sm">
				<li
					v-for="(option, index ) in optionsModel"
					:key="index"
					class="hover:tw-bg-gray-200"
					@click.prevent.stop="onClickOption(option, index)"
				>
					<span
							class="tw-flex tw-py-2 tw-px-4 transition 
							duration-300 hover:tw-bg-gray-200 hover:tw-cursor-pointer">
						{{ option.name || option.id }}
					</span>    
				</li>
        </ul>
		</SimplePopover>
	</div>
</template>
<script>
import { defineComponent, nextTick, ref} from "vue";
import { SimplePopover } from '../../../base/index'

const isTruncated =(element) => {
	return element.scrollWidth > element.clientWidth;
}

export default defineComponent({
	components:{
		SimplePopover
	},
	props: {
		columns: {
			type: Array,
			default: () => [],
		},
		column: {
			type: Object,
			default: () => ({}),
		},
		row: {
			type: Object,
			default: () => ({}),
		},
	},
	setup(props, { emit }) {
		const show = ref(false)
		const optionsModel = ref()

		const onClick = (event, item, index)=>{
			if(isTruncated(event.currentTarget.parentNode) &&
				event.currentTarget.getBoundingClientRect().right > event.currentTarget.parentNode.getBoundingClientRect().right){
					optionsModel.value = props.row[props.column.field].slice(index);

					nextTick(()=>{
						show.value = true;
					})
				return	
			}

			show.value = false;
			onClickOption(item, index);
		}

		const onClickOption = (option, index)=>{
		}

		const onClose = ()=>{
			show.value = false;
		}

		return {
			show,
			optionsModel,
			onClose,
			onClickOption,
			onClick
		};
	},
});
</script>
<style
